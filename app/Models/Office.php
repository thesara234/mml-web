<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    protected $fillable = [
        'code', 'name', 'office_type', 'country', 'country_code', 'city', 'address', 'phone', 'email',
        'opening_hours', 'currency', 'has_store', 'bank_name', 'bank_account_name', 'bank_account_number', 'is_active',
    ];

    protected $casts = ['has_store' => 'boolean', 'is_active' => 'boolean'];

    public function staff(): HasMany
    {
        return $this->hasMany(StaffUser::class);
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** Offices that can take money from senders. */
    public function scopeSending(Builder $q): Builder
    {
        return $q->whereIn('office_type', ['sender', 'both']);
    }

    /** Offices that can pay out to receivers. */
    public function scopeReceiving(Builder $q): Builder
    {
        return $q->whereIn('office_type', ['receiver', 'both']);
    }

    public function canSend(): bool
    {
        return in_array($this->office_type, ['sender', 'both'], true);
    }

    public function canReceive(): bool
    {
        return in_array($this->office_type, ['receiver', 'both'], true);
    }

    public function flag(): string
    {
        // Regional-indicator emoji built from the ISO code (NZ → 🇳🇿, TO → 🇹🇴)
        $cc = strtoupper($this->country_code ?: 'UN');

        return mb_chr(0x1F1E6 + ord($cc[0]) - 65).mb_chr(0x1F1E6 + ord($cc[1]) - 65);
    }
}
