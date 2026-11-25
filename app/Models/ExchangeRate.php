<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = ['local_currency', 'foreign_currency', 'rate', 'effective_date', 'set_by_staff_id', 'is_active'];

    protected $casts = [
        'rate' => 'float',
        'effective_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function setByStaff(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class, 'set_by_staff_id');
    }

    /** Latest active rate for a corridor that is already in effect. */
    public static function current(string $local, string $foreign): ?self
    {
        return static::where('local_currency', strtoupper($local))
            ->where('foreign_currency', strtoupper($foreign))
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();
    }

    /** The latest rate for every active corridor. */
    public static function board()
    {
        return static::where('is_active', true)
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')->orderByDesc('id')
            ->get()
            ->unique(fn ($r) => $r->local_currency.'-'.$r->foreign_currency)
            ->sortBy(fn ($r) => $r->local_currency.$r->foreign_currency)
            ->values();
    }
}
