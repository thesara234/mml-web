<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Models\FeeTier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeController extends Controller
{
    public function index()
    {
        return view('staff.settings.fees', [
            'tiers' => FeeTier::orderBy('local_currency')->orderBy('foreign_currency')->orderBy('min_amount')->get()
                ->groupBy(fn ($t) => $t->local_currency.' → '.$t->foreign_currency),
            'currencies' => array_keys(config('mml.currencies')),
            'defaultPercent' => config('mml.default_fee_percent'),
        ]);
    }

    public function store(Request $request)
    {
        $codes = array_keys(config('mml.currencies'));
        $data = $request->validate([
            'local_currency' => ['required', Rule::in($codes)],
            'foreign_currency' => ['required', Rule::in($codes), 'different:local_currency'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gt:min_amount'],
            'fee_type' => ['required', Rule::in(['flat', 'percent'])],
            'fee_value' => ['required', 'numeric', 'min:0', 'max:10000'],
        ]);
        FeeTier::create($data + ['is_active' => true]);

        return back()->with('success', 'Fee tier added.');
    }

    public function destroy(FeeTier $feeTier)
    {
        $feeTier->delete();

        return back()->with('success', 'Fee tier removed.');
    }
}
