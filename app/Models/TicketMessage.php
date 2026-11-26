<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    use SetsCreatedAt;

    public $timestamps = false;

    protected $fillable = ['ticket_id', 'user_id', 'is_staff', 'body', 'attachment', 'created_at'];

    protected function casts(): array
    {
        return ['is_staff' => 'boolean', 'created_at' => 'datetime'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
