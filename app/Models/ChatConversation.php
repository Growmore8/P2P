<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    protected $fillable = ['token', 'user_id', 'name', 'email', 'status', 'unread_admin', 'unread_visitor', 'ip', 'page', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }

    public function displayName(): string
    {
        return $this->user?->name ?: ($this->name ?: 'Visitor #' . $this->id);
    }

    public function contactEmail(): ?string
    {
        return $this->user?->email ?: $this->email;
    }
}
