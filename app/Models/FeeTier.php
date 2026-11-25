<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeTier extends Model
{
    protected $fillable = ['local_currency', 'foreign_currency', 'min_amount', 'max_amount', 'fee_type', 'fee_value', 'is_active'];

    protected $casts = [
        'min_amount' => 'float',
        'max_amount' => 'float',
        'fee_value' => 'float',
        'is_active' => 'boolean',
    ];

    public function label(): string
    {
        $to = $this->max_amount !== null ? number_format($this->max_amount, 2) : '+';

        return number_format($this->min_amount, 2).($this->max_amount !== null ? ' – '.$to : '+');
    }

    public function describeFee(): string
    {
        return $this->fee_type === 'percent'
            ? rtrim(rtrim(number_format($this->fee_value, 2), '0'), '.').'%'
            : $this->local_currency.' '.number_format($this->fee_value, 2);
    }
}
