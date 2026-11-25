<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $fillable = ['transaction_id', 'stock_item_id', 'name', 'unit', 'quantity', 'unit_price', 'line_total'];

    protected $casts = ['unit_price' => 'float', 'line_total' => 'float', 'quantity' => 'integer'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
