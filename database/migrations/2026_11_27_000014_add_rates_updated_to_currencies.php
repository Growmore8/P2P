<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who last changed a currency's buy / sell rate, and when (admins and managers can change rates). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->foreignId('rates_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rates_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rates_updated_by');
            $table->dropColumn('rates_updated_at');
        });
    }
};
