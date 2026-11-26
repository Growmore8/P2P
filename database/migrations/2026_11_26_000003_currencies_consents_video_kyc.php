<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fiat currencies the desk trades in, each with its own rates and limits.
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique();          // INR, LKR, AED…
            $table->string('name', 60);
            $table->string('symbol', 8);
            $table->decimal('buy_rate', 14, 4);          // fiat per USDT the client pays
            $table->decimal('sell_rate', 14, 4);         // fiat per USDT the client receives
            $table->decimal('min_amount', 16, 2)->default(0);
            $table->decimal('max_amount', 16, 2)->default(0);
            $table->decimal('monthly_buy_limit', 16, 2)->default(0);   // 0 = no limit
            $table->decimal('monthly_sell_limit', 16, 2)->default(0);
            $table->decimal('tds_percent', 5, 2)->default(0);          // withheld on sell orders
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Methods clients can add to RECEIVE money (sell orders).
        Schema::create('payout_methods', function (Blueprint $table) {
            $table->id();
            $table->char('currency', 3);
            $table->json('countries')->nullable();      // null = every country using this currency
            $table->string('type', 12);                 // upi | bank | wallet
            $table->string('title');
            $table->string('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('deposit_methods', function (Blueprint $table) {
            $table->char('currency', 3)->default('INR')->after('type');
            $table->json('countries')->nullable()->after('currency');
            $table->string('ifsc', 20)->nullable()->change();   // also used for SWIFT / branch codes
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->char('currency', 3)->default('INR')->after('type');
            $table->foreignId('payout_method_id')->nullable()->after('currency')->constrained()->nullOnDelete();
            $table->string('ifsc', 20)->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->char('currency', 3)->default('INR')->after('network');
            $table->renameColumn('inr_amount', 'fiat_amount');
            $table->renameColumn('payout_inr', 'payout_fiat');
        });

        Schema::table('kyc_submissions', function (Blueprint $table) {
            $table->string('video')->nullable()->after('selfie');
        });

        // Every agreement a client accepts, with version, time, IP and device.
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);               // terms | privacy | risk | age | kyc | p2p_order
            $table->string('version', 20);
            $table->string('context', 40)->nullable(); // e.g. order number
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'type']);
        });

        // Audit trail of admin actions.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });

        // Move existing INR rates & limits from settings into the INR currency row.
        $s = DB::table('settings')->pluck('value', 'key');
        DB::table('currencies')->insert([
            'code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹',
            'buy_rate' => $s['buy_rate'] ?? 93.50, 'sell_rate' => $s['sell_rate'] ?? 91.00,
            'min_amount' => $s['min_inr'] ?? 1000, 'max_amount' => $s['max_inr'] ?? 500000,
            'monthly_buy_limit' => $s['monthly_buy_limit'] ?? 1000000, 'monthly_sell_limit' => $s['monthly_sell_limit'] ?? 1000000,
            'tds_percent' => (($s['tds_enabled'] ?? '1') === '1') ? ($s['tds_percent'] ?? 1) : 0,
            'is_active' => true, 'sort' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('settings')->whereIn('key', ['buy_rate', 'sell_rate', 'min_inr', 'max_inr', 'monthly_buy_limit', 'monthly_sell_limit', 'tds_enabled', 'tds_percent'])->delete();

        // Existing payout accounts were UPI / Indian bank accounts.
        $now = now();
        $upi = DB::table('payout_methods')->insertGetId(['currency' => 'INR', 'type' => 'upi', 'title' => 'UPI', 'instructions' => 'Any UPI ID in your own name', 'is_active' => true, 'sort' => 1, 'created_at' => $now, 'updated_at' => $now]);
        $bank = DB::table('payout_methods')->insertGetId(['currency' => 'INR', 'type' => 'bank', 'title' => 'Bank account (IMPS / NEFT)', 'instructions' => 'Indian bank account in your own name', 'is_active' => true, 'sort' => 2, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('bank_accounts')->where('type', 'upi')->update(['payout_method_id' => $upi]);
        DB::table('bank_accounts')->where('type', 'bank')->update(['payout_method_id' => $bank]);
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('consents');
        Schema::table('kyc_submissions', fn (Blueprint $t) => $t->dropColumn('video'));
        Schema::table('orders', function (Blueprint $t) {
            $t->renameColumn('fiat_amount', 'inr_amount');
            $t->renameColumn('payout_fiat', 'payout_inr');
            $t->dropColumn('currency');
        });
        Schema::table('bank_accounts', function (Blueprint $t) {
            $t->dropConstrainedForeignId('payout_method_id');
            $t->dropColumn('currency');
        });
        Schema::table('deposit_methods', fn (Blueprint $t) => $t->dropColumn(['currency', 'countries']));
        Schema::dropIfExists('payout_methods');
        Schema::dropIfExists('currencies');
    }
};
