<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_type', 'staff_user_id', 'body', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class, 'staff_user_id');
    }

    public function toChatArray(): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type,
            'sender_name' => $this->sender_type === 'staff'
                ? ($this->staff?->given_name ?? 'MML Staff').' · MML'
                : $this->conversation?->customer?->given_name,
            'body' => $this->body,
            'read' => $this->read_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'time' => $this->created_at?->format('g:i a'),
        ];
    }
}
