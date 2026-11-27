<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** "Payment link" deposit methods (e.g. a Razorpay.me / gateway payment page). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_methods', function (Blueprint $table) {
            $table->string('pay_url', 255)->nullable()->after('upi_id');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_methods', fn (Blueprint $t) => $t->dropColumn('pay_url'));
    }
};
