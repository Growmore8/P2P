<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Managers: staff who check that a client's payment arrived and mark the order "Reviewed";
 * the admin then approves it. Also last-seen / last-login-IP tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('is_admin')->index(); // null = client · 'manager'
            $table->timestamp('last_seen_at')->nullable()->after('last_login_at');
            $table->string('last_login_ip', 45)->nullable()->after('last_seen_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('review_status', 20)->nullable()->after('status'); // received | not_received
            $table->string('review_note', 255)->nullable()->after('review_status');
            $table->foreignId('reviewed_by')->nullable()->after('review_note')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_status', 'review_note', 'reviewed_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'last_seen_at', 'last_login_ip']);
        });
    }
};
