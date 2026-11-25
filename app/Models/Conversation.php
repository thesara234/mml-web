<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['customer_id', 'office_id', 'transaction_id', 'subject', 'status', 'last_message_at'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** Customer messages staff have not read yet. */
    public function unreadForStaff(): int
    {
        return $this->messages()->where('sender_type', 'customer')->whereNull('read_at')->count();
    }

    /** Staff messages the customer has not read yet (shown as a badge in the app and portal). */
    public static function unreadForCustomer(Customer $customer): int
    {
        return Message::where('sender_type', 'staff')->whereNull('read_at')
            ->whereHas('conversation', fn ($q) => $q->where('customer_id', $customer->id))->count();
    }

    public static function forCustomer(Customer $customer): self
    {
        return static::firstOrCreate(
            ['customer_id' => $customer->id],
            ['office_id' => $customer->created_by_office_id, 'subject' => 'Support', 'status' => 'open']
        );
    }
}
