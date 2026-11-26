<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\Currency;
use App\Models\DepositMethod;
use App\Models\KycSubmission;
use App\Models\Network;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class P2PFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('is_admin', true)->first();
        DepositMethod::query()->update(['is_active' => true]);
        Network::where('code', 'TRC20')->update(['deposit_address' => 'TXYZabcdefghijkmnopqrstuvwxyz12345']);
    }

    /** The 6-digit code from the last OTP email sent to this address (mail is faked in tests). */
    private static function lastOtp(string $email): string
    {
        $mail = Mail::sent(OtpMail::class, fn ($m) => $m->hasTo($email))->last();
        self::assertNotNull($mail, "No OTP email sent to {$email}");

        return $mail->code;
    }

    /** A real 1x1 PNG (no GD extension needed). */
    private static function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    }

    /** Minimal WebM (EBML) header so the server detects video/webm. */
    private static function video(): UploadedFile
    {
        $webm = hex2bin('1a45dfa3a34286810142f7810142f2810442f381084282847765626d4287810442858102185380670100000000000000');

        return UploadedFile::fake()->createWithContent('kyc.webm', $webm . str_repeat("\0", 512));
    }

    private function customer(string $kyc = 'approved'): User
    {
        $u = User::create(['name' => 'Test User', 'email' => uniqid() . '@t.com', 'phone' => (string) random_int(6000000000, 9999999999), 'password' => 'secret123']);
        $u->forceFill(['kyc_status' => $kyc, 'email_verified_at' => now()])->save();
        foreach (Consent::ACCOUNT_TYPES as $t) {
            Consent::create(['user_id' => $u->id, 'type' => $t, 'version' => Consent::currentVersion()]);
        }

        return $u;
    }

    public function test_public_pages_load(): void
    {
        foreach (['/', '/p2p', '/services', '/about', '/insights', '/contact', '/legal', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_register_and_submit_kyc_then_admin_approves(): void
    {
        $this->post('/register', [
            'name' => 'Ravi Kumar', 'email' => 'ravi@example.com', 'country' => 'IN', 'phone' => '98765 43210',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!', 'terms' => '1', 'risk' => '1', 'age' => '1',
        ])->assertRedirect('/verify-email');

        $user = User::where('email', 'ravi@example.com')->first();
        $this->get('/kyc')->assertRedirect('/verify-email'); // must verify email first
        $this->post('/verify-email', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/verify-email', ['code' => self::lastOtp('ravi@example.com')])->assertRedirect('/kyc');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->post('/trade', ['side' => 'buy'])->assertRedirect('/kyc'); // ordering blocked until KYC approved

        $img = fn () => UploadedFile::fake()->createWithContent('doc.png', self::png());
        $this->post('/kyc', [
            'full_name' => 'Ravi Kumar', 'dob' => '1990-01-01', 'pan_number' => 'abcde1234f', 'aadhaar_number' => '234567891234',
            'address' => '1 Main St', 'city' => 'Dharmapuri', 'state' => 'Tamil Nadu', 'pincode' => '636701',
            'pan_image' => $img(), 'aadhaar_front' => $img(), 'aadhaar_back' => $img(), 'selfie' => $img(), 'consent' => '1',
            'video' => self::video(), 'video_consent' => '1',
        ])->assertRedirect('/kyc/verifying')->assertSessionHasNoErrors();

        $kyc = KycSubmission::first();
        $this->assertCount(5, $kyc->checks); // PAN, Aadhaar front/back, selfie, video
        $agents = collect($kyc->checks)->pluck('agent');
        $this->assertSame($agents->count(), $agents->unique()->count(), 'each document gets a different team name');
        $this->get('/kyc/verifying')->assertOk()->assertSee('Aadhaar — Front')->assertSee($agents[0]);
        $this->assertSame('ABCDE1234F', $kyc->pan_number);
        $this->assertSame('1234', $kyc->aadhaar_last4);
        $this->assertSame('pending', $user->fresh()->kyc_status);

        // another customer cannot view the documents
        $this->actingAs($this->customer())->get("/files/kyc/{$kyc->id}/pan_image")->assertForbidden();

        $this->actingAs($this->admin)->get("/files/kyc/{$kyc->id}/pan_image")->assertOk();
        $this->actingAs($this->admin)->post("/admin/kyc/{$kyc->id}/approve")->assertRedirect();
        $this->assertSame('approved', $user->fresh()->kyc_status);

        $this->actingAs($user->fresh())->get('/trade')->assertOk();
    }

    public function test_international_customer_registers_and_submits_passport_kyc(): void
    {
        // Sri Lankan number typed with the local trunk "0"
        $this->post('/register', [
            'name' => 'Sajeenthan', 'email' => 'saj@example.com', 'country' => 'LK', 'phone' => '0779895511',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!', 'terms' => '1', 'risk' => '1', 'age' => '1',
        ])->assertRedirect('/verify-email');

        $user = User::where('email', 'saj@example.com')->first();
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->assertSame('+94779895511', $user->phone);
        $this->assertSame('LK', $user->country);

        // same number again (typed with +94) is rejected as duplicate
        $this->post('/logout');
        $this->post('/register', [
            'name' => 'Other', 'email' => 'other@example.com', 'country' => 'LK', 'phone' => '+94 77 989 5511',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!', 'terms' => '1', 'risk' => '1', 'age' => '1',
        ])->assertSessionHasErrors('phone');

        $this->actingAs($user)->get('/kyc')->assertOk()->assertSee('Proof of address')->assertDontSee('Aadhaar number');

        $img = fn () => UploadedFile::fake()->createWithContent('doc.png', self::png());
        $this->actingAs($user)->post('/kyc', [
            'full_name' => 'Sajeenthan', 'dob' => '1992-03-03', 'id_type' => 'passport', 'id_number' => 'n1234567',
            'address' => '5 Galle Rd', 'city' => 'Colombo', 'pincode' => '00300',
            'pan_image' => $img(), 'aadhaar_back' => $img(), 'selfie' => $img(), 'consent' => '1', 'video' => self::video(), 'video_consent' => '1',
        ])->assertRedirect('/kyc/verifying')->assertSessionHasNoErrors();

        $kyc = KycSubmission::first();
        $this->assertSame('passport', $kyc->id_type);
        $this->assertSame('N1234567', $kyc->id_number);
        $this->assertNull($kyc->pan_number);

        $this->actingAs($this->admin)->get("/admin/kyc/{$kyc->id}")->assertOk()->assertSee('Sri Lanka')->assertSee('N1234567');
        $this->actingAs($this->admin)->get('/admin/kyc')->assertOk();
        $this->actingAs($this->admin)->get("/admin/users/{$user->id}")->assertOk();
    }

    public function test_onramp_style_login_flow_with_email_and_phone(): void
    {
        $u = $this->customer();
        $u->forceFill(['email' => 'flow@example.com', 'phone' => '+94779895511', 'country' => 'LK', 'password' => 'Passw0rd!'])->save();

        $this->get('/login')->assertOk()->assertSee('Sign up or login');

        // terms must be accepted
        // Admin email → no "I agree" needed → Admin login screen → admin panel
        $this->post('/login/identify', ['identifier' => 'admin@srmfintech.local', 'country' => 'IN'])->assertSessionHasNoErrors()->assertRedirect('/login/password');
        $this->get('/login/password')->assertOk()->assertSee('Admin login')->assertDontSee('Keep me logged in');
        $this->post('/login', ['password' => 'ChangeMe@123'])->assertRedirect('/admin');
        $this->assertDatabaseHas('activity_logs', ['action' => 'admin.login']);
        $this->post('/logout');

        // Client email → normal "Welcome back" screen
        $this->post('/login/identify', ['identifier' => 'flow@example.com', 'country' => 'IN'])->assertRedirect('/login/password');
        $this->get('/login/password')->assertSee('Welcome back')->assertDontSee('Admin login');
        $this->get('/login/restart');

        // existing email → password step → login
        $this->post('/login/identify', ['identifier' => 'FLOW@example.com', 'country' => 'IN', 'terms' => '1'])->assertRedirect('/login/password');
        $this->get('/login/password')->assertOk()->assertSee('flow@example.com');
        $this->post('/login', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/login', ['password' => 'Passw0rd!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->get('/dashboard')->assertRedirect('/trade?side=buy');
        $this->post('/logout');

        // existing phone typed locally with the trunk 0 → password step → login by phone
        $this->post('/login/identify', ['identifier' => '0779895511', 'country' => 'LK', 'terms' => '1'])->assertRedirect('/login/password');
        $this->post('/login', ['password' => 'Passw0rd!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout');

        // new email → sign-up step with the email prefilled
        $this->post('/login/identify', ['identifier' => 'new@example.com', 'country' => 'AE', 'terms' => '1'])->assertRedirect('/register');
        $this->get('/register')->assertOk()->assertSee('new@example.com');
    }

    public function test_account_pages_password_change_and_monthly_limit(): void
    {
        $u = $this->customer();
        $u->forceFill(['password' => 'Passw0rd!'])->save();
        foreach (['/account', '/account/limits', '/account/security', '/accounts', '/orders', '/trade', '/trade?side=sell', '/kyc'] as $url) {
            $this->actingAs($u)->get($url)->assertOk();
        }

        $this->actingAs($u)->put('/account/password', ['current_password' => 'nope', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertSessionHasErrors('current_password');
        $this->actingAs($u)->put('/account/password', ['current_password' => 'Passw0rd!', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertRedirect('/account/security');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPass123', $u->fresh()->password));

        Currency::where('code', 'INR')->update(['monthly_buy_limit' => 15000]);
        $order = fn ($amt) => $this->actingAs($u)->post('/trade', ['side' => 'buy', 'network' => 'TRC20', 'amount' => $amt,
            'wallet_address' => 'TXYZabcdefghijkmnopqrstuvwxyz12345', 'deposit_method_id' => DepositMethod::first()->id, 'confirm' => '1']);
        $order('10000')->assertSessionHasNoErrors();
        $order('6000')->assertSessionHasErrors('amount'); // 10,000 + 6,000 > 15,000
        $this->assertSame(1, Order::count());
        $this->actingAs($u)->get('/account/limits')->assertSee('10,000 used of ₹15,000');
    }

    public function test_invalid_phone_for_country_is_rejected(): void
    {
        $base = ['name' => 'A', 'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!', 'terms' => '1', 'risk' => '1', 'age' => '1'];
        $this->post('/register', $base + ['email' => 'a@x.com', 'country' => 'IN', 'phone' => '0779895511'])->assertSessionHasErrors('phone');
        $this->post('/register', $base + ['email' => 'b@x.com', 'country' => 'AE', 'phone' => '123'])->assertSessionHasErrors('phone');
        $this->post('/register', $base + ['email' => 'c@x.com', 'country' => 'XX', 'phone' => '501234567'])->assertSessionHasErrors('country');
        $this->assertSame(1, User::count()); // only the seeded admin
    }

    public function test_admin_can_reject_kyc_with_reason(): void
    {
        $u = $this->customer('pending');
        $kyc = $u->kycSubmissions()->create([
            'full_name' => 'X', 'dob' => '1990-01-01', 'pan_number' => 'ABCDE1234F', 'aadhaar_last4' => '1234', 'address' => 'a',
            'city' => 'c', 'state' => 's', 'pincode' => '123456', 'pan_image' => 'a', 'aadhaar_front' => 'b', 'aadhaar_back' => 'c', 'selfie' => 'd',
        ]);
        $this->actingAs($this->admin)->post("/admin/kyc/{$kyc->id}/reject", ['reject_reason' => 'Blurry'])->assertRedirect();
        $this->assertSame('rejected', $u->fresh()->kyc_status);
        $this->assertSame('Blurry', $kyc->fresh()->reject_reason);
    }

    public function test_full_buy_flow_with_server_side_pricing(): void
    {
        $u = $this->customer();
        $method = DepositMethod::where('type', 'upi')->first();

        $this->actingAs($u)->post('/trade', [
            'side' => 'buy', 'network' => 'TRC20', 'amount' => '9350', 'rate' => '1', // client "rate" is ignored
            'wallet_address' => 'TXYZabcdefghijkmnopqrstuvwxyz12345', 'deposit_method_id' => $method->id, 'confirm' => '1',
        ])->assertRedirect();

        $order = Order::first();
        $this->assertSame('93.50', $order->rate);
        $this->assertSame('99.00', $order->usdt_amount); // 9350 / 93.5 = 100 − 1 fee
        $this->assertSame(Order::PENDING, $order->status);
        $this->assertSame($method->upi_id, $order->deposit_snapshot['upi_id']);

        // other users cannot see it
        $this->actingAs($this->customer())->get("/orders/{$order->order_no}")->assertNotFound();

        $this->actingAs($u)->get("/orders/{$order->order_no}")->assertOk()->assertSee($method->upi_id);
        $this->actingAs($u)->post("/orders/{$order->order_no}/payment", [
            'user_reference' => '412345678901', 'user_proof' => UploadedFile::fake()->createWithContent('pay.png', self::png()),
        ])->assertRedirect();
        $this->assertSame(Order::SUBMITTED, $order->fresh()->status);

        $this->actingAs($this->admin)->post("/admin/orders/{$order->order_no}/accept")->assertRedirect();
        $this->assertSame(Order::PROCESSING, $order->fresh()->status);

        $this->actingAs($this->admin)->post("/admin/orders/{$order->order_no}/complete", ['admin_reference' => str_repeat('a', 64)])->assertRedirect();
        $order->refresh();
        $this->assertSame(Order::COMPLETED, $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertCount(4, $order->logs);

        // completed orders cannot be rejected
        $this->actingAs($this->admin)->post("/admin/orders/{$order->order_no}/reject", ['reject_reason' => 'x']);
        $this->assertSame(Order::COMPLETED, $order->fresh()->status);
    }

    public function test_sell_flow_deducts_tds_and_admin_can_reject(): void
    {
        $u = $this->customer();
        $acc = $u->bankAccounts()->create(['type' => 'upi', 'holder_name' => 'Test User', 'upi_id' => 'test@okbank']);

        $this->actingAs($u)->post('/trade', [
            'side' => 'sell', 'network' => 'TRC20', 'amount' => '100', 'bank_account_id' => $acc->id, 'confirm' => '1',
        ])->assertRedirect();

        $order = Order::first();
        $this->assertSame('9100.00', $order->fiat_amount);
        $this->assertSame('91.00', $order->tds_amount);
        $this->assertSame('9009.00', $order->payout_fiat);
        $this->assertSame('TXYZabcdefghijkmnopqrstuvwxyz12345', $order->deposit_address);

        $this->actingAs($u)->post("/orders/{$order->order_no}/payment", ['user_reference' => str_repeat('b', 64)])->assertRedirect();
        $this->actingAs($this->admin)->post("/admin/orders/{$order->order_no}/reject", ['reject_reason' => 'USDT not received'])->assertRedirect();
        $this->assertSame(Order::REJECTED, $order->fresh()->status);
        $this->actingAs($u)->get("/orders/{$order->order_no}")->assertSee('USDT not received');
    }

    public function test_limits_wallet_validation_and_sell_with_others_account(): void
    {
        $u = $this->customer();
        $other = $this->customer();
        $foreign = $other->bankAccounts()->create(['type' => 'upi', 'holder_name' => 'O', 'upi_id' => 'o@okbank']);
        $method = DepositMethod::first();

        $this->actingAs($u)->post('/trade', ['side' => 'buy', 'network' => 'TRC20', 'amount' => '500', 'wallet_address' => 'TXYZabcdefghijkmnopqrstuvwxyz12345', 'deposit_method_id' => $method->id, 'confirm' => '1'])
            ->assertSessionHasErrors('amount');
        $this->actingAs($u)->post('/trade', ['side' => 'buy', 'network' => 'TRC20', 'amount' => '5000', 'wallet_address' => '0xabc', 'deposit_method_id' => $method->id, 'confirm' => '1'])
            ->assertSessionHasErrors('wallet_address');
        $this->actingAs($u)->post('/trade', ['side' => 'sell', 'network' => 'TRC20', 'amount' => '50', 'bank_account_id' => $foreign->id, 'confirm' => '1'])
            ->assertSessionHasErrors('bank_account_id');
        $this->actingAs($u)->post('/trade', ['side' => 'sell', 'network' => 'BEP20', 'amount' => '50', 'bank_account_id' => $foreign->id, 'confirm' => '1'])
            ->assertSessionHasErrors('network'); // no deposit address set
        $this->assertSame(0, Order::count());
    }

    public function test_unpaid_orders_expire_and_cannot_accept_payment(): void
    {
        $u = $this->customer();
        $this->actingAs($u)->post('/trade', ['side' => 'buy', 'network' => 'TRC20', 'amount' => '5000', 'wallet_address' => 'TXYZabcdefghijkmnopqrstuvwxyz12345', 'deposit_method_id' => DepositMethod::first()->id, 'confirm' => '1']);
        $order = Order::first();
        $this->travel(31)->minutes();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame(Order::EXPIRED, $order->fresh()->status);
        $this->actingAs($u)->post("/orders/{$order->order_no}/payment", ['user_reference' => '412345678901', 'user_proof' => UploadedFile::fake()->createWithContent('p.png', self::png())]);
        $this->assertSame(Order::EXPIRED, $order->fresh()->status);
    }

    public function test_admin_area_is_protected_and_settings_update_rates(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs($this->customer())->get('/admin')->assertForbidden();

        foreach (['/admin', '/admin/orders', '/admin/kyc', '/admin/users', '/admin/methods', '/admin/methods/create', '/admin/networks', '/admin/settings'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        $this->actingAs($this->admin)->put('/admin/settings', array_merge(Setting::values(), ['terms_version' => '1.0', 'trading_enabled' => '1', 'kyc_video_required' => '1']))->assertRedirect();
        $inr = Currency::where('code', 'INR')->first();
        $this->actingAs($this->admin)->put("/admin/currencies/{$inr->id}", array_merge($inr->only(['name', 'symbol', 'min_amount', 'max_amount', 'monthly_buy_limit', 'monthly_sell_limit', 'tds_percent', 'sort']),
            ['buy_rate' => '95', 'sell_rate' => '92', 'is_active' => '1']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(95.0, $inr->fresh()->buy_rate);
        $this->assertTrue(\App\Models\ActivityLog::where('action', 'currency.updated')->exists());
    }

    public function test_registration_records_consents_and_new_terms_version_requires_reaccept(): void
    {
        $this->post('/register', [
            'name' => 'Asha', 'email' => 'asha@example.com', 'country' => 'IN', 'phone' => '9123456780',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!', 'terms' => '1',
        ])->assertSessionHasErrors(['risk', 'age']);

        $this->post('/register', [
            'name' => 'Asha', 'email' => 'asha@example.com', 'country' => 'IN', 'phone' => '9123456780',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!', 'terms' => '1', 'risk' => '1', 'age' => '1',
        ])->assertRedirect('/verify-email');
        $u = User::where('email', 'asha@example.com')->first();
        $u->forceFill(['email_verified_at' => now()])->save();
        $this->assertEqualsCanonicalizing(Consent::ACCOUNT_TYPES, $u->consents()->pluck('type')->all());
        $this->assertNotNull($u->consents()->first()->ip);

        // Admin publishes new terms → client must accept again
        Setting::put(['terms_version' => '2.0']);
        $this->actingAs($u)->get('/trade')->assertRedirect('/consent');
        $this->actingAs($u)->post('/consent', ['terms' => '1'])->assertSessionHasErrors(['risk', 'age']);
        $this->actingAs($u)->post('/consent', ['terms' => '1', 'risk' => '1', 'age' => '1'])->assertRedirect();
        $this->actingAs($u)->get('/trade')->assertOk();
        $this->assertSame(4, $u->consents()->where('version', '2.0')->count());
    }

    public function test_country_wise_currency_and_methods_for_sri_lanka(): void
    {
        $u = $this->customer();
        $u->forceFill(['country' => 'LK', 'phone' => '+94771234567'])->save();

        // LKR not active yet → not available
        $this->actingAs($u)->get('/trade')->assertOk()->assertSee('available in Sri Lanka yet');

        Currency::create(['code' => 'LKR', 'name' => 'Sri Lankan Rupee', 'symbol' => 'Rs ', 'buy_rate' => 310, 'sell_rate' => 300,
            'min_amount' => 3000, 'max_amount' => 1000000, 'tds_percent' => 0, 'is_active' => true]);
        $lk = DepositMethod::create(['type' => 'bank', 'currency' => 'LKR', 'countries' => ['LK'], 'title' => 'Bank transfer (CEFTS)',
            'account_holder' => 'SRM', 'account_number' => '123456789', 'ifsc' => 'BCEYLKLX', 'bank_name' => 'Bank of Ceylon', 'is_active' => true]);
        $pm = \App\Models\PayoutMethod::create(['currency' => 'LKR', 'type' => 'bank', 'title' => 'Sri Lankan bank account', 'is_active' => true]);

        // Indian methods are not offered to a Sri Lankan client, LKR ones are
        $this->actingAs($u)->get('/trade')->assertOk()->assertSee('Bank transfer (CEFTS)')->assertDontSee('UPI (GPay');

        $this->actingAs($u)->post('/trade', ['side' => 'buy', 'network' => 'TRC20', 'amount' => '31310',
            'wallet_address' => 'TXYZabcdefghijkmnopqrstuvwxyz12345', 'deposit_method_id' => $lk->id, 'confirm' => '1'])->assertSessionHasNoErrors();
        $order = Order::latest('id')->first();
        $this->assertSame('LKR', $order->currency);
        $this->assertSame('100.00', $order->usdt_amount); // 31310 / 310 = 101 − 1 fee
        $this->assertSame('Rs 31,310.00', $order->money($order->fiat_amount));
        $this->assertTrue(Consent::where('type', 'p2p_order')->where('context', $order->order_no)->exists());

        // An Indian deposit method can't be used for an LKR order
        $this->actingAs($u)->post('/trade', ['side' => 'buy', 'network' => 'TRC20', 'amount' => '31310',
            'wallet_address' => 'TXYZabcdefghijkmnopqrstuvwxyz12345', 'deposit_method_id' => DepositMethod::where('currency', 'INR')->first()->id, 'confirm' => '1'])
            ->assertSessionHasErrors('deposit_method_id');

        // Receive methods: only LKR types offered; SWIFT-style codes accepted
        $this->actingAs($u)->get('/accounts')->assertSee('Sri Lankan bank account')->assertDontSee('Bank account (IMPS / NEFT)');
        $this->actingAs($u)->post('/accounts', ['payout_method_id' => $pm->id, 'holder_name' => 'Test User', 'bank_name' => 'Bank of Ceylon',
            'account_number' => '0012345678', 'account_number_confirmation' => '0012345678', 'ifsc' => 'BCEYLKLX'])->assertSessionHasNoErrors();
        $this->assertSame('LKR', $u->bankAccounts()->first()->currency);
    }

    public function test_admin_dashboard_and_new_admin_pages(): void
    {
        $u = $this->customer();
        $o = $u->orders()->create(['order_no' => 'SRMTEST1', 'side' => 'buy', 'network' => 'TRC20', 'currency' => 'INR', 'rate' => 93.5,
            'fiat_amount' => 9350, 'usdt_amount' => 99, 'status' => Order::COMPLETED, 'completed_at' => now()]);
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('Total clients')->assertSee('99.00')->assertSee('₹9,350.00');
        foreach (['/admin?period=today', '/admin?period=all', '/admin/orders?side=buy', '/admin/orders?side=sell', '/admin/currencies',
            '/admin/payout-methods', '/admin/history', '/admin/history?tab=orders', '/admin/history?tab=consents', "/admin/users/{$u->id}", '/admin/users?country=IN'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
        $this->actingAs($this->admin)->post('/admin/payout-methods', ['currency' => 'INR', 'type' => 'wallet', 'title' => 'Paytm wallet', 'is_active' => '1', 'countries' => ['IN']])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payout_methods', ['title' => 'Paytm wallet']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payout_method.created']);
    }

    public function test_duplicate_email_cannot_register_again(): void
    {
        $u = $this->customer();
        $data = ['name' => 'Copy', 'email' => strtoupper($u->email), 'country' => 'IN', 'phone' => '9000011111',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!', 'terms' => '1', 'risk' => '1', 'age' => '1'];
        $this->post('/register', $data)->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', $u->email)->count());

        // Typing an existing email on the login screen goes to the password step, never sign-up
        $this->post('/login/identify', ['identifier' => $u->email, 'country' => 'IN'])->assertRedirect('/login/password');
    }

    public function test_forgot_password_with_email_otp(): void
    {
        $u = $this->customer();
        $u->forceFill(['email' => 'reset@example.com', 'password' => 'OldPass123'])->save();

        $this->get('/forgot-password')->assertOk()->assertSee('Forgot password?');
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertRedirect('/forgot-password/code'); // same answer, no mail
        Mail::assertNotSent(OtpMail::class, fn ($m) => $m->hasTo('nobody@example.com'));

        $this->post('/forgot-password', ['email' => 'RESET@example.com'])->assertRedirect('/forgot-password/code');
        $this->get('/forgot-password/code')->assertOk()->assertSee('reset@example.com');
        $this->post('/forgot-password', ['email' => 'reset@example.com'])->assertSessionHasErrors('email'); // 60s resend cooldown

        $this->get('/forgot-password/new')->assertRedirect('/forgot-password'); // not verified yet
        foreach (range(1, 5) as $i) {
            $this->post('/forgot-password/code', ['code' => '111111']);
        }
        $this->post('/forgot-password/code', ['code' => self::lastOtp('reset@example.com')])->assertSessionHasErrors('code'); // locked after 5 tries

        $this->travel(61)->seconds();
        $this->post('/forgot-password/resend')->assertSessionHasNoErrors();
        $this->post('/forgot-password/code', ['code' => self::lastOtp('reset@example.com')])->assertRedirect('/forgot-password/new');
        $this->post('/forgot-password/new', ['password' => 'NewPass456', 'password_confirmation' => 'NewPass456'])->assertRedirect('/login');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPass456', $u->fresh()->password));

        // the used code cannot be reused
        $this->get('/forgot-password/new')->assertRedirect('/forgot-password');
    }

    public function test_support_ticket_flow_with_emails_and_admin_reply(): void
    {
        $u = $this->customer();
        $other = $this->customer();
        $mine = $u->orders()->create(['order_no' => 'SRMTK1', 'side' => 'buy', 'network' => 'TRC20', 'currency' => 'INR', 'rate' => 93.5, 'fiat_amount' => 9350, 'usdt_amount' => 99, 'status' => Order::SUBMITTED]);
        $theirs = $other->orders()->create(['order_no' => 'SRMTK2', 'side' => 'buy', 'network' => 'TRC20', 'currency' => 'INR', 'rate' => 93.5, 'fiat_amount' => 9350, 'usdt_amount' => 99, 'status' => Order::SUBMITTED]);

        $this->actingAs($u)->get('/support')->assertOk()->assertSee('Raise a ticket');
        $this->actingAs($u)->get('/support/new?category=funds&order=SRMTK1')->assertOk()->assertSee('Funds not received');
        $this->actingAs($u)->post('/support', ['category' => 'funds', 'subject' => 'Hi', 'message' => 'short'])->assertSessionHasErrors(['subject', 'message']);

        // linking someone else's order is ignored
        $this->actingAs($u)->post('/support', ['category' => 'funds', 'order_id' => $theirs->id, 'subject' => 'Paid but no USDT', 'message' => 'I paid 9350 via UPI, UTR 412345678901.',
            'attachment' => UploadedFile::fake()->createWithContent('pay.png', self::png())])->assertRedirect();
        $t = \App\Models\Ticket::first();
        $this->assertNull($t->order_id);
        $this->assertMatchesRegularExpression('/^TKT-\d{6}-[A-Z0-9]{5}$/', $t->ticket_no);
        $this->actingAs($u)->get("/support/{$t->ticket_no}")->assertOk()->assertSee('Ticket submitted successfully')->assertSee($t->ticket_no);
        Mail::assertSent(\App\Mail\TicketMail::class, fn ($m) => $m->type === 'created' && $m->hasTo($u->email));
        Mail::assertSent(\App\Mail\TicketMail::class, fn ($m) => $m->type === 'staff_new' && $m->hasTo(Setting::get('support_email')));

        $this->actingAs($u)->post('/support', ['category' => 'buy', 'order_id' => $mine->id, 'subject' => 'Order delay', 'message' => 'My order is still processing.'])->assertRedirect();
        $this->assertSame($mine->id, \App\Models\Ticket::latest('id')->first()->order_id);

        // privacy: other clients can't see the ticket or its attachment
        $msg = $t->messages()->first();
        $this->actingAs($other)->get("/support/{$t->ticket_no}")->assertNotFound();
        $this->actingAs($other)->get("/files/ticket/{$msg->id}")->assertForbidden();
        $this->actingAs($u)->get("/files/ticket/{$msg->id}")->assertOk();

        // admin sees it, replies → client gets an email with the reply
        $this->actingAs($this->admin)->get('/admin/tickets')->assertOk()->assertSee($t->ticket_no);
        $this->actingAs($this->admin)->get("/admin/tickets/{$t->ticket_no}")->assertOk()->assertSee('I paid 9350');
        $this->actingAs($this->admin)->post("/admin/tickets/{$t->ticket_no}/reply", ['message' => 'We have verified your payment.', 'status' => 'answered'])->assertSessionHasNoErrors();
        $this->assertSame('answered', $t->fresh()->status);
        Mail::assertSent(\App\Mail\TicketMail::class, fn ($m) => $m->type === 'reply' && $m->hasTo($u->email) && $m->msg?->body === 'We have verified your payment.');

        // client replies → back to open, support inbox notified
        $this->actingAs($u)->post("/support/{$t->ticket_no}/reply", ['message' => 'Thanks, received!'])->assertSessionHasNoErrors();
        $this->assertSame('open', $t->fresh()->status);
        Mail::assertSent(\App\Mail\TicketMail::class, fn ($m) => $m->type === 'staff_reply');

        // closed tickets accept no more replies
        $this->actingAs($u)->post("/support/{$t->ticket_no}/close");
        $this->actingAs($u)->post("/support/{$t->ticket_no}/reply", ['message' => 'one more'])->assertSessionHas('error');
        $this->assertSame(3, $t->messages()->count());
    }

    public function test_activity_feed_shows_real_orders_with_masked_names_only(): void
    {
        $u = $this->customer();
        $u->forceFill(['name' => 'Ravi Kumar', 'country' => 'IN'])->save();
        $u->orders()->create(['order_no' => 'SRMFEED1', 'side' => 'buy', 'network' => 'TRC20', 'currency' => 'INR', 'rate' => 93.5,
            'fiat_amount' => 40579, 'usdt_amount' => 434, 'status' => Order::COMPLETED, 'completed_at' => now()->subSeconds(20)]);
        $u->orders()->create(['order_no' => 'SRMFEED2', 'side' => 'buy', 'network' => 'TRC20', 'currency' => 'INR', 'rate' => 93.5,
            'fiat_amount' => 9350, 'usdt_amount' => 99, 'status' => Order::SUBMITTED]); // not completed → never shown

        $feed = $this->getJson('/activity.json')->assertOk()->json();
        $this->assertCount(1, $feed);
        $this->assertSame('Ra*** K.', $feed[0]['nick']);
        $this->assertSame('434', $feed[0]['usdt']);
        $raw = json_encode($feed);
        $this->assertStringNotContainsString('Kumar', $raw);
        $this->assertStringNotContainsString('40579', $raw);
        $this->get('/')->assertOk()->assertSee('Ra*** K.');
    }

    public function test_blocked_user_is_logged_out(): void
    {
        $u = $this->customer();
        $u->forceFill(['status' => 'blocked'])->save();
        $this->actingAs($u)->get('/dashboard')->assertRedirect('/login');
    }
}
