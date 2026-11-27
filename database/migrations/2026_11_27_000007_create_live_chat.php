<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();                 // visitor's browser key (stored in an encrypted cookie)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('status', 12)->default('open');         // open | closed
            $table->unsignedInteger('unread_admin')->default(0);
            $table->unsignedInteger('unread_visitor')->default(0);
            $table->string('ip', 45)->nullable();
            $table->string('page', 255)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'last_message_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender', 10);                          // visitor | admin | bot
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // admin who replied
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
