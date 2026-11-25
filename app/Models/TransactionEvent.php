<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionEvent extends Model
{
    protected $fillable = ['transaction_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'actor_name', 'remarks'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
