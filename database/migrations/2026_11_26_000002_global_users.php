<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->change(); // E.164, e.g. +94771234567
            $table->char('country', 2)->default('IN')->after('phone');
        });
        // Existing Indian numbers (10 digits, no +) → +91XXXXXXXXXX
        DB::table('users')->where('phone', 'not like', '+%')->get(['id', 'phone'])->each(
            fn ($u) => DB::table('users')->where('id', $u->id)->update(['phone' => '+91' . $u->phone])
        );

        Schema::table('kyc_submissions', function (Blueprint $table) {
            $table->char('country', 2)->default('IN')->after('user_id');
            $table->string('id_type', 20)->default('pan_aadhaar')->after('country'); // pan_aadhaar | passport | national_id | driving_licence
            $table->string('id_number', 30)->nullable()->after('id_type');
            $table->string('pan_number', 10)->nullable()->change();
            $table->string('aadhaar_last4', 4)->nullable()->change();
            $table->string('state', 80)->nullable()->change();
            $table->string('pincode', 12)->change();
            $table->index('id_number');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_submissions', function (Blueprint $table) {
            $table->dropIndex(['id_number']);
            $table->dropColumn(['country', 'id_type', 'id_number']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
