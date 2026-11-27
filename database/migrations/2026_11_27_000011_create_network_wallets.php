<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Several USDT deposit addresses per network; new sell orders take turns between the active ones. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->string('address', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['network_id', 'address']);
        });

        // Move each network's single address into the new table.
        foreach (DB::table('networks')->whereNotNull('deposit_address')->where('deposit_address', '!=', '')->get() as $n) {
            DB::table('network_wallets')->insert([
                'network_id' => $n->id, 'address' => $n->deposit_address, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('network_wallets');
    }
};
