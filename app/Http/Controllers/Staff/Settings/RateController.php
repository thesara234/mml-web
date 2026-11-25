<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RateController extends Controller
{
    public function index()
    {
        return view('staff.settings.rates', [
            'board' => ExchangeRate::board(),
            'history' => ExchangeRate::with('setByStaff')->latest('effective_date')->latest('id')->paginate(15),
            'currencies' => array_keys(config('mml.currencies')),
        ]);
    }

    public function store(Request $request)
    {
        $codes = array_keys(config('mml.currencies'));
        $data = $request->validate([
            'local_currency' => ['required', Rule::in($codes)],
            'foreign_currency' => ['required', Rule::in($codes), 'different:local_currency'],
            'rate' => ['required', 'numeric', 'gt:0', 'lt:100000'],
            'effective_date' => ['required', 'date'],
            'with_inverse' => ['nullable', 'boolean'],
        ]);

        $staffId = $request->user('staff')->id;
        ExchangeRate::updateOrCreate(
            ['local_currency' => $data['local_currency'], 'foreign_currency' => $data['foreign_currency'], 'effective_date' => $data['effective_date']],
            ['rate' => $data['rate'], 'set_by_staff_id' => $staffId, 'is_active' => true]
        );

        if ($request->boolean('with_inverse')) {
            ExchangeRate::updateOrCreate(
                ['local_currency' => $data['foreign_currency'], 'foreign_currency' => $data['local_currency'], 'effective_date' => $data['effective_date']],
                ['rate' => round(1 / $data['rate'], 6), 'set_by_staff_id' => $staffId, 'is_active' => true]
            );
        }

        return back()->with('success', "Rate {$data['local_currency']} → {$data['foreign_currency']} saved.");
    }
}
