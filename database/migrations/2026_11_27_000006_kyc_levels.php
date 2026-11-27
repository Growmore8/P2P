<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tiered verification like Onramp:
 *   Level 1 = Identity (documents)   → kyc_submissions (existing)
 *   Level 2 = Video verification     → kyc_upgrades (level 2)
 *   Level 3 = EDD (source of funds)  → kyc_upgrades (level 3)
 * Monthly limits now depend on the client's level (per currency).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('kyc_level')->default(0)->after('kyc_status');
        });
        DB::table('users')->where('kyc_status', 'approved')->update(['kyc_level' => 1]);

        Schema::create('kyc_upgrades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');                 // 2 = video, 3 = EDD
            $table->string('status', 20)->default('pending');     // pending | approved | rejected
            $table->json('files');                                // e.g. {"video": "..."} or {"bank_statement": "...", "income_proof": "..."}
            $table->json('details')->nullable();                  // video: script + language · EDD: occupation, income, source…
            $table->string('reject_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['level', 'status', 'created_at']);
        });

        Schema::table('currencies', function (Blueprint $table) {
            $table->json('level_limits')->nullable()->after('max_amount');
        });
        // Old single monthly limit becomes Level 2; Level 1 = 10%, Level 3 = 4×.
        foreach (DB::table('currencies')->get() as $c) {
            $buy = (float) $c->monthly_buy_limit;
            $sell = (float) $c->monthly_sell_limit;
            $limits = $c->code === 'INR'
                ? ['1' => [50000, 50000], '2' => [500000, 500000], '3' => [2000000, 2000000]]
                : ['1' => [round($buy / 10), round($sell / 10)], '2' => [$buy, $sell], '3' => [$buy * 4, $sell * 4]];
            DB::table('currencies')->where('id', $c->id)->update(['level_limits' => json_encode($limits)]);
        }
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['monthly_buy_limit', 'monthly_sell_limit']);
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('monthly_buy_limit', 16, 2)->default(0);
            $table->decimal('monthly_sell_limit', 16, 2)->default(0);
            $table->dropColumn('level_limits');
        });
        Schema::dropIfExists('kyc_upgrades');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('kyc_level'));
    }
};
