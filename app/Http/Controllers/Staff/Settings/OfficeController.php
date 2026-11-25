<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfficeController extends Controller
{
    public function index()
    {
        return view('staff.settings.offices', [
            'offices' => Office::withCount('staff')->orderBy('country')->orderBy('name')->get(),
            'currencies' => array_keys(config('mml.currencies')),
        ]);
    }

    public function store(Request $request)
    {
        Office::create($this->validated($request) + ['is_active' => true]);

        return back()->with('success', 'Office added.');
    }

    public function update(Request $request, Office $office)
    {
        $office->update($this->validated($request, $office) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', "{$office->name} updated.");
    }

    private function validated(Request $request, ?Office $office = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('offices', 'code')->ignore($office?->id)],
            'name' => ['required', 'string', 'max:120'],
            'office_type' => ['required', Rule::in(['sender', 'receiver', 'both'])],
            'country' => ['required', 'string', 'max:60'],
            'country_code' => ['required', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'currency' => ['required', Rule::in(array_keys(config('mml.currencies')))],
            'bank_name' => ['nullable', 'string', 'max:80'],
            'bank_account_name' => ['nullable', 'string', 'max:80'],
            'bank_account_number' => ['nullable', 'string', 'max:40'],
        ]);
        $data['has_store'] = $request->boolean('has_store');
        $data['country_code'] = strtoupper($data['country_code']);

        return $data;
    }
}
