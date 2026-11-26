<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One-time codes sent by email (email verification, password reset). Only a hash is stored.
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('purpose', 20);          // verify_email | reset_password
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['email', 'purpose']);
        });

        // Automatic checks run on each KYC file at submission (shown to the client and the admin).
        Schema::table('kyc_submissions', function (Blueprint $table) {
            $table->json('checks')->nullable()->after('video');
        });

        // Accounts created before email verification existed are treated as verified.
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('kyc_submissions', fn (Blueprint $t) => $t->dropColumn('checks'));
        Schema::dropIfExists('otp_codes');
    }
};
