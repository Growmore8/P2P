<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('kyc_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->date('dob');
            $table->string('pan_number', 10);
            $table->string('aadhaar_last4', 4);
            $table->string('address');
            $table->string('city', 80);
            $table->string('state', 80);
            $table->string('pincode', 6);
            $table->string('pan_image');
            $table->string('aadhaar_front');
            $table->string('aadhaar_back');
            $table->string('selfie');
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->string('reject_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // bank | upi
            $table->string('holder_name');
            $table->string('account_number', 30)->nullable();
            $table->string('ifsc', 11)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('upi_id')->nullable();
            $table->timestamps();
        });

        // Admin-configured methods where customers PAY INR (buy orders)
        Schema::create('deposit_methods', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10); // upi | bank
            $table->string('title');
            $table->string('upi_id')->nullable();
            $table->string('qr_image')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number', 30)->nullable();
            $table->string('ifsc', 11)->nullable();
            $table->string('bank_name')->nullable();
            $table->text('instructions')->nullable();
            $table->decimal('min_amount', 14, 2)->default(0);
            $table->decimal('max_amount', 14, 2)->default(0); // 0 = no limit
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Supported networks + admin deposit address for USDT (sell orders)
        Schema::create('networks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // TRC20, BEP20...
            $table->string('name');
            $table->decimal('fee', 10, 2)->default(0); // USDT fee charged on buy
            $table->string('deposit_address')->nullable();
            $table->string('address_regex')->nullable();
            $table->boolean('buy_enabled')->default(true);
            $table->boolean('sell_enabled')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('side', 4); // buy | sell
            $table->string('network', 20);
            $table->decimal('rate', 10, 2);
            $table->decimal('inr_amount', 14, 2);   // buy: INR paid · sell: gross INR value
            $table->decimal('usdt_amount', 14, 2);  // buy: USDT delivered · sell: USDT received
            $table->decimal('fee_usdt', 10, 2)->default(0);
            $table->decimal('tds_amount', 14, 2)->default(0);
            $table->decimal('payout_inr', 14, 2)->default(0); // sell: net INR paid to user
            $table->string('status', 24)->default('pending_payment');
            // buy
            $table->string('wallet_address')->nullable();
            $table->foreignId('deposit_method_id')->nullable()->constrained()->nullOnDelete();
            $table->json('deposit_snapshot')->nullable();
            // sell
            $table->string('deposit_address')->nullable();
            $table->json('payout_snapshot')->nullable();
            // proofs
            $table->string('user_reference')->nullable(); // UTR (buy) or tx hash (sell)
            $table->string('user_proof')->nullable();
            $table->string('admin_reference')->nullable(); // tx hash sent (buy) or payout UTR (sell)
            $table->string('reject_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24);
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_logs');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('networks');
        Schema::dropIfExists('deposit_methods');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('kyc_submissions');
        Schema::dropIfExists('settings');
    }
};
