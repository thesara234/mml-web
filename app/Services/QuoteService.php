<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\FeeTier;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    /**
     * @param  string  $mode  "send"    → $amount is what the sender sends (local currency)
     *                        "receive" → $amount is what the receiver should get (foreign currency)
     */
    public function quote(float $amount, string $local, string $foreign, string $mode = 'send', float $discount = 0): Quote
    {
        $local = strtoupper($local);
        $foreign = strtoupper($foreign);

        if ($local === $foreign) {
            $rateValue = 1.0;
            $rateId = null;
        } else {
            $rate = ExchangeRate::current($local, $foreign);
            if (! $rate) {
                throw ValidationException::withMessages([
                    'foreign_currency' => "We don't have a {$local} → {$foreign} rate yet. Please contact your MML office.",
                ]);
            }
            $rateValue = (float) $rate->rate;
            $rateId = $rate->id;
        }

        $transfer = $mode === 'receive' ? round($amount / $rateValue, 2) : round($amount, 2);
        if ($transfer <= 0) {
            throw ValidationException::withMessages(['transfer_amount' => 'Please enter an amount greater than zero.']);
        }

        $max = (float) config('mml.max_transfer_amount', 10000);
        if ($transfer > $max) {
            throw ValidationException::withMessages([
                'transfer_amount' => "The most you can send in one transfer is {$local} ".number_format($max, 2).'.',
            ]);
        }

        $converted = $mode === 'receive' ? round($amount, 2) : round($transfer * $rateValue, 2);
        [$fee, $feeDescription] = $this->fee($transfer, $local, $foreign);
        $discount = max(0, min(round($discount, 2), $fee)); // a discount can waive the fee, not the principal

        return new Quote(
            localCurrency: $local,
            foreignCurrency: $foreign,
            rate: $rateValue,
            rateId: $rateId,
            transferAmount: $transfer,
            convertedAmount: $converted,
            serviceFee: $fee,
            discount: $discount,
            totalPayable: round($transfer + $fee - $discount, 2),
            feeDescription: $feeDescription,
        );
    }

    /** @return array{0: float, 1: string} */
    public function fee(float $transfer, string $local, string $foreign): array
    {
        $tier = FeeTier::where('is_active', true)
            ->where('local_currency', $local)
            ->where('foreign_currency', $foreign)
            ->where('min_amount', '<=', $transfer)
            ->where(fn ($q) => $q->whereNull('max_amount')->orWhere('max_amount', '>=', $transfer))
            ->orderByDesc('min_amount')
            ->first();

        if (! $tier) {
            $pct = (float) config('mml.default_fee_percent', 3);

            return [round($transfer * $pct / 100, 2), "{$pct}% standard fee"];
        }

        $fee = $tier->fee_type === 'percent'
            ? round($transfer * $tier->fee_value / 100, 2)
            : round($tier->fee_value, 2);

        return [$fee, $tier->fee_type === 'percent' ? $tier->describeFee().' fee' : 'Flat fee'];
    }
}
