<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A permanent, unique client ID (e.g. SRM4829173) shown to staff and the client. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('client_id', 12)->nullable()->unique()->after('id');
        });

        // Give every existing account an ID.
        foreach (DB::table('users')->whereNull('client_id')->orderBy('id')->pluck('id') as $id) {
            DB::table('users')->where('id', $id)->update(['client_id' => User::newClientId()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
