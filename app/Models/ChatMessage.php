<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use SetsCreatedAt;

    public $timestamps = false;

    protected $fillable = ['chat_conversation_id', 'sender', 'user_id', 'body', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Shape sent to the chat widget / admin poller. */
    public function toWidget(): array
    {
        return [
            'id' => $this->id,
            'from' => $this->sender,
            'name' => match ($this->sender) {
                'admin' => 'SRM Support',
                'bot' => 'SRM Assistant · automatic reply',
                default => 'You',
            },
            'body' => $this->body,
            'time' => $this->created_at?->format('h:i A'),
        ];
    }
}
