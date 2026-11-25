<?php

namespace App\Services;

/** Immutable result of a price calculation. */
class Quote
{
    public function __construct(
        public readonly string $localCurrency,
        public readonly string $foreignCurrency,
        public readonly float $rate,
        public readonly ?int $rateId,
        public readonly float $transferAmount,
        public readonly float $convertedAmount,
        public readonly float $serviceFee,
        public readonly float $discount,
        public readonly float $totalPayable,
        public readonly string $feeDescription,
    ) {}

    public function toArray(): array
    {
        return [
            'local_currency' => $this->localCurrency,
            'foreign_currency' => $this->foreignCurrency,
            'rate' => $this->rate,
            'transfer_amount' => $this->transferAmount,
            'converted_amount' => $this->convertedAmount,
            'service_fee' => $this->serviceFee,
            'discount' => $this->discount,
            'total_payable' => $this->totalPayable,
            'fee_description' => $this->feeDescription,
        ];
    }
}
