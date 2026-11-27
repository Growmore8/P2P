<?php

namespace Database\Seeders;

use App\Models\Consent;
use App\Models\Currency;
use App\Models\DepositMethod;
use App\Models\KycSubmission;
use App\Models\Network;
use App\Models\Order;
use App\Models\PayoutMethod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * LOCAL TESTING ONLY — demo clients, an LKR currency, sample orders.
 *   php artisan db:seed --class=DemoSeeder
 * All demo clients use the password: Demo@1234
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Demo@1234';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder must never run in production.');
        }

        $this->call(DatabaseSeeder::class);

        // Payments setup so buying and selling work out of the box
        DepositMethod::where('currency', 'INR')->update(['is_active' => true]);
        Network::where('code', 'TRC20')->update(['deposit_address' => 'TXYZabcdefghijkmnopqrstuvwxyz12345']);
        Currency::firstOrCreate(['code' => 'LKR'], [
            'name' => 'Sri Lankan Rupee', 'symbol' => 'Rs ', 'buy_rate' => 310, 'sell_rate' => 300,
            'min_amount' => 3000, 'max_amount' => 1500000, 'level_limits' => ['1' => [150000, 150000], '2' => [1500000, 1500000], '3' => [6000000, 6000000]],
            'tds_percent' => 0, 'is_active' => true, 'sort' => 2,
        ]);
        DepositMethod::firstOrCreate(['title' => 'Bank transfer (CEFTS)'], [
            'type' => 'bank', 'currency' => 'LKR', 'countries' => ['LK'], 'account_holder' => 'SRM FINTECH SOLUTIONS',
            'account_number' => '0001234567', 'ifsc' => 'BCEYLKLX', 'bank_name' => 'Bank of Ceylon', 'is_active' => true,
            'instructions' => "Transfer the exact amount.\nPay only from your own account.",
        ]);
        $lkPayout = PayoutMethod::firstOrCreate(['title' => 'Sri Lankan bank account'], ['currency' => 'LKR', 'type' => 'bank', 'is_active' => true]);
        $upi = PayoutMethod::where('currency', 'INR')->where('type', 'upi')->first();

        // 1) Verified Indian client — can buy & sell right away
        $india = $this->client('india.client@demo.test', 'Ravi Kumar', 'IN', '+919800000001', 'approved');
        $india->bankAccounts()->firstOrCreate(['upi_id' => 'ravi@okbank'], ['type' => 'upi', 'currency' => 'INR', 'payout_method_id' => $upi?->id, 'holder_name' => 'Ravi Kumar']);
        $this->kyc($india, 'approved');

        // 2) Verified Sri Lankan client — trades in LKR
        $lk = $this->client('srilanka.client@demo.test', 'Kasun Perera', 'LK', '+94770000001', 'approved');
        $lk->bankAccounts()->firstOrCreate(['account_number' => '0098765432'], [
            'type' => 'bank', 'currency' => 'LKR', 'payout_method_id' => $lkPayout->id, 'holder_name' => 'Kasun Perera',
            'bank_name' => 'Commercial Bank', 'ifsc' => 'CCEYLKLX',
        ]);
        $this->kyc($lk, 'approved');

        // 3) New client — not verified yet (test the KYC + video flow)
        $this->client('new.client@demo.test', 'Anitha R', 'IN', '+919800000002', 'none');

        // 4) Client waiting for KYC review (test admin approve / reject)
        $pending = $this->client('pending.client@demo.test', 'Priya S', 'IN', '+919800000003', 'pending');
        $this->kyc($pending, 'pending');

        // Sample completed orders over the last two weeks (fills the admin dashboard)
        if (! $india->orders()->exists()) {
            mt_srand(11);
            foreach (range(1, 18) as $i) {
                $u = $i % 4 === 0 ? $lk : $india;
                $cur = $u->country === 'LK' ? 'LKR' : 'INR';
                $buy = $i % 3 !== 0;
                $usdt = mt_rand(60, 700);
                $rate = $cur === 'LKR' ? ($buy ? 310 : 300) : ($buy ? 93.5 : 91);
                $fiat = round($usdt * $rate, 2);
                $tds = (! $buy && $cur === 'INR') ? round($fiat * 0.01, 2) : 0;
                $when = now()->subDays(mt_rand(0, 13))->subMinutes(mt_rand(0, 600));
                $o = $u->orders()->create([
                    'order_no' => 'SRM' . $when->format('ymd') . strtoupper(Str::random(5)),
                    'side' => $buy ? 'buy' : 'sell', 'network' => 'TRC20', 'currency' => $cur, 'rate' => $rate,
                    'fiat_amount' => $fiat, 'usdt_amount' => $usdt, 'fee_usdt' => $buy ? 1 : 0, 'tds_amount' => $tds,
                    'payout_fiat' => $buy ? 0 : $fiat - $tds, 'status' => Order::COMPLETED,
                    'wallet_address' => $buy ? 'TXYZabcdefghijkmnopqrstuvwxyz12345' : null,
                    'deposit_address' => $buy ? null : 'TXYZabcdefghijkmnopqrstuvwxyz12345',
                    'user_reference' => $buy ? (string) mt_rand(400000000000, 499999999999) : Str::random(64),
                    'admin_reference' => $buy ? Str::lower(Str::random(64)) : (string) mt_rand(500000000000, 599999999999),
                    'paid_at' => $when, 'completed_at' => $when->copy()->addMinutes(18),
                ]);
                $o->forceFill(['created_at' => $when])->save();
                $o->logs()->create(['status' => Order::COMPLETED, 'note' => 'Demo order', 'created_at' => $when]);
            }
        }

        // International demo clients (local testing only) so the homepage feed shows several countries.
        $intl = [
            ['AE', 'AED', 'Aisha Al Mansoori', 'uae.client@demo.test', '+971500000001', 'د.إ ', 3.72, 3.62],
            ['GB', 'GBP', 'James Walker', 'uk.client@demo.test', '+447700000001', '£', 0.81, 0.77],
            ['MY', 'MYR', 'Nur Aisyah', 'malaysia.client@demo.test', '+60120000001', 'RM', 4.55, 4.35],
            ['SG', 'SGD', 'Wei Ling Tan', 'singapore.client@demo.test', '+6581000001', 'S$', 1.37, 1.31],
            ['NG', 'NGN', 'Chinedu Okafor', 'nigeria.client@demo.test', '+2348030000001', '₦', 1580, 1530],
            ['US', 'USD', 'Emily Carter', 'usa.client@demo.test', '+12025550101', '$', 1.01, 0.99],
        ];
        foreach ($intl as [$cc, $cur, $name, $email, $phone, $sym, $buyRate, $sellRate]) {
            // Demo currencies stay inactive: they exist only so demo orders have a currency.
            Currency::firstOrCreate(['code' => $cur], [
                'name' => $cur, 'symbol' => $sym, 'buy_rate' => $buyRate, 'sell_rate' => $sellRate,
                'min_amount' => 0, 'max_amount' => 0, 'tds_percent' => 0, 'is_active' => false, 'sort' => 50,
            ]);
            $u = $this->client($email, $name, $cc, $phone, 'approved');
            if ($u->orders()->exists()) {
                continue;
            }
            foreach (range(1, 2) as $k) {
                $buy = $k === 1 || mt_rand(0, 1);
                $usdt = mt_rand(80, 900);
                $rate = $buy ? $buyRate : $sellRate;
                $when = now()->subHours(mt_rand(1, 72));
                $o = $u->orders()->create([
                    'order_no' => 'SRM' . $when->format('ymd') . strtoupper(Str::random(5)),
                    'side' => $buy ? 'buy' : 'sell', 'network' => 'TRC20', 'currency' => $cur, 'rate' => $rate,
                    'fiat_amount' => round($usdt * $rate, 2), 'usdt_amount' => $usdt, 'fee_usdt' => $buy ? 1 : 0,
                    'payout_fiat' => $buy ? 0 : round($usdt * $rate, 2), 'status' => Order::COMPLETED,
                    'wallet_address' => $buy ? 'TXYZabcdefghijkmnopqrstuvwxyz12345' : null,
                    'deposit_address' => $buy ? null : 'TXYZabcdefghijkmnopqrstuvwxyz12345',
                    'paid_at' => $when, 'completed_at' => $when->copy()->addMinutes(15),
                ]);
                $o->forceFill(['created_at' => $when])->save();
            }
        }

        $this->command?->info('Demo clients ready — password for all: ' . self::PASSWORD);
    }

    private function client(string $email, string $name, string $country, string $phone, string $kyc): User
    {
        $u = User::firstOrCreate(['email' => $email], ['name' => $name, 'phone' => $phone, 'country' => $country, 'password' => self::PASSWORD]);
        $u->forceFill(['kyc_status' => $kyc, 'kyc_level' => $kyc === 'approved' ? max(1, (int) $u->kyc_level) : 0, 'email_verified_at' => now()])->save();
        foreach (Consent::ACCOUNT_TYPES as $t) {
            Consent::firstOrCreate(['user_id' => $u->id, 'type' => $t, 'version' => Consent::currentVersion()], ['ip' => '127.0.0.1', 'user_agent' => 'DemoSeeder']);
        }

        return $u;
    }

    private function kyc(User $u, string $status): void
    {
        if ($u->kycSubmissions()->exists()) {
            return;
        }
        $india = $u->country === 'IN';
        $u->kycSubmissions()->create([
            'country' => $u->country, 'id_type' => $india ? 'pan_aadhaar' : 'passport', 'id_number' => $india ? null : 'N' . mt_rand(1000000, 9999999),
            'full_name' => $u->name, 'dob' => '1994-04-04', 'pan_number' => $india ? 'ABCDE' . mt_rand(1000, 9999) . 'F' : null,
            'aadhaar_last4' => $india ? (string) mt_rand(1000, 9999) : null, 'address' => '12 Demo Street', 'city' => $india ? 'Dharmapuri' : 'Colombo',
            'state' => $india ? 'Tamil Nadu' : null, 'pincode' => $india ? '636701' : '00300',
            // Demo placeholders — the files don't exist, so admin will see empty previews for these demo KYCs.
            'pan_image' => 'demo/missing.png', 'aadhaar_front' => 'demo/missing.png', 'aadhaar_back' => 'demo/missing.png', 'selfie' => 'demo/missing.png',
            'status' => $status, 'reviewed_at' => $status === 'approved' ? now() : null,
        ]);
    }
}
