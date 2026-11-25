<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StockItem extends Model
{
    protected $fillable = [
        'office_id', 'sku', 'name', 'category', 'description', 'unit', 'price', 'currency',
        'quantity_on_hand', 'image_path', 'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity_on_hand' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        // Built-in product pictures ship in public/images; uploaded photos live on the public disk.
        return str_starts_with($this->image_path, 'images/')
            ? asset($this->image_path)
            : Storage::disk('public')->url($this->image_path);
    }

    public function isLow(): bool
    {
        return $this->quantity_on_hand <= 5;
    }
}
