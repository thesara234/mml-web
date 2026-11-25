<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Office;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ReceiverController extends Controller
{
    /** The sender's saved receivers. */
    public function index(Request $request)
    {
        $receivers = $request->user()->receivers()->where('customers.is_active', true)->orderBy('given_name')->get();
        $offices = Office::whereIn('id', $receivers->pluck('pivot.preferred_office_id')->filter())->get()->keyBy('id');

        // Saved receivers were registered by/for this sender, so their personal details
        // can be shown to confirm who is collecting.
        return response()->json($receivers->map(fn (Customer $c) => self::row($c) + [
            'residential_address' => $c->residential_address,
            'date_of_birth' => $c->date_of_birth?->toDateString(),
            'gender' => $c->gender,
            'relationship' => $c->pivot->relationship,
            'preferred_office' => ($o = $offices->get($c->pivot->preferred_office_id)) ? Transaction::officeArray($o) : null,
        ])->values());
    }

    /**
     * Find a receiver who is already registered with MML. To protect privacy, a name search
     * only returns limited details; customer code or full phone number gives an exact match.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->validate(['q' => ['required', 'string', 'min:3', 'max:60']])['q']);
        $me = $request->user();

        $results = Customer::where('is_active', true)
            ->where('id', '!=', $me->id)
            ->receiverLookup($q)
            ->orderBy('given_name')->limit(15)->get();

        return response()->json($results->map(fn ($c) => self::row($c, masked: true))->values());
    }

    /** Save someone to "My receivers". */
    public function link(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'relationship' => ['nullable', 'string', 'max:40'],
            'preferred_office_id' => ['nullable', 'integer', 'exists:offices,id'],
        ]);
        abort_if($customer->id === $request->user()->id, 422, "You can't add yourself as a receiver.");

        $request->user()->receivers()->syncWithoutDetaching([$customer->id => [
            'relationship' => $data['relationship'] ?? null,
            'preferred_office_id' => $data['preferred_office_id'] ?? null,
        ]]);

        return response()->json(self::row($customer) + ['relationship' => $data['relationship'] ?? null], 201);
    }

    public static function row(Customer $c, bool $masked = false): array
    {
        $phone = $c->phone;
        if ($masked && $phone) {
            $phone = str_repeat('•', max(0, strlen($phone) - 3)).substr($phone, -3);
        }

        return [
            'id' => $c->id,
            'customer_code' => $c->customer_code,
            'full_name' => $c->full_name,
            'given_name' => $c->given_name,
            'family_name' => $c->family_name,
            'city' => $c->city,
            'country' => $c->country,
            'phone' => $phone,
            'photo_url' => $masked ? null : $c->photo_url,
            'is_verified' => $c->is_verified,
        ];
    }
}
