<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Shared\LookupController as SharedLookup;
use App\Models\ExchangeRate;
use App\Models\FeeTier;
use App\Models\Office;
use App\Models\Transaction;
use Illuminate\Http\Request;

class LookupController extends SharedLookup
{
    /** Everything the app needs to draw the send-money screens, in one round trip. */
    public function bootstrap(Request $request)
    {
        $rates = ExchangeRate::board();
        $currencies = config('mml.currencies');

        return response()->json([
            'customer' => AuthController::profile($request->user()),
            'corridors' => $rates->map(fn ($r) => [
                'local_currency' => $r->local_currency,
                'foreign_currency' => $r->foreign_currency,
                'rate' => $r->rate,
                'effective_date' => $r->effective_date?->toDateString(),
                'fees' => FeeTier::where('is_active', true)->where('local_currency', $r->local_currency)
                    ->where('foreign_currency', $r->foreign_currency)->orderBy('min_amount')->get()
                    ->map(fn ($f) => ['min' => $f->min_amount, 'max' => $f->max_amount, 'type' => $f->fee_type, 'value' => $f->fee_value])->values(),
            ])->values(),
            'currencies' => collect($currencies)->map(fn ($c, $code) => ['code' => $code] + $c)->values(),
            'sending_offices' => Office::active()->sending()->orderBy('name')->get()->map(fn ($o) => Transaction::officeArray($o))->values(),
            'receiving_offices' => Office::active()->receiving()->orderBy('name')->get()->map(fn ($o) => Transaction::officeArray($o))->values(),
            'purposes' => Transaction::PURPOSES,
            'income_sources' => Transaction::INCOME_SOURCES,
            'payment_methods' => collect(Transaction::PAYMENT_METHODS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'max_transfer_amount' => (float) config('mml.max_transfer_amount'),
        ]);
    }

    public function stores()
    {
        return response()->json(
            Office::active()->receiving()->where('has_store', true)->orderBy('name')->get()
                ->map(fn ($o) => Transaction::officeArray($o))->values()
        );
    }
}
