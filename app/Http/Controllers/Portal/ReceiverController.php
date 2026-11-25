<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Api\ReceiverController as ApiReceivers;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Office;
use App\Services\CustomerRegistration;
use Illuminate\Http\Request;

/** Sender customers register their own receivers (business rule: web only). */
class ReceiverController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user('customer');

        return view('portal.receivers.index', [
            'receivers' => $me->receivers()->orderBy('given_name')->get(),
            'offices' => Office::all()->keyBy('id'),
        ]);
    }

    public function create()
    {
        return view('portal.receivers.create', [
            'offices' => Office::active()->receiving()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CustomerRegistration $registration)
    {
        $me = $request->user('customer');
        $data = $request->validate(CustomerRegistration::rules() + [
            'relationship' => ['required', 'string', 'max:40'],
            'preferred_office_id' => ['nullable', 'integer', 'exists:offices,id'],
        ], CustomerRegistration::messages());

        $pivot = ['relationship' => $data['relationship'], 'preferred_office_id' => $data['preferred_office_id'] ?? null];
        unset($data['relationship'], $data['preferred_office_id']);

        $receiver = $registration->create($request, $data, [
            'registered_by_customer_id' => $me->id,
            'created_by_office_id' => $me->created_by_office_id,
            'app_access_enabled' => false, // the receiver's office sets up their own login when they visit
        ]);
        $me->receivers()->attach($receiver->id, $pivot);

        if ($request->input('back') === 'send') {
            return redirect()->route('portal.send', ['receiver' => $receiver->id])
                ->with('success', "{$receiver->full_name} is registered. Carry on with your transfer.");
        }

        return redirect()->route('portal.receivers.index')
            ->with('success', "{$receiver->full_name} is registered ({$receiver->customer_code}). They'll show photo ID when collecting.");
    }

    /** Add a receiver who is already registered with MML. */
    public function link(Request $request)
    {
        $me = $request->user('customer');
        $data = $request->validate([
            'receiver_customer_id' => ['required', 'integer', 'exists:customers,id'],
            'relationship' => ['nullable', 'string', 'max:40'],
        ]);
        abort_if((int) $data['receiver_customer_id'] === $me->id, 422, "You can't add yourself.");
        $me->receivers()->syncWithoutDetaching([$data['receiver_customer_id'] => ['relationship' => $data['relationship'] ?? null]]);

        return $request->expectsJson()
            ? response()->json(ApiReceivers::row(Customer::find($data['receiver_customer_id'])) + ['relationship' => $data['relationship'] ?? null])
            : back()->with('success', 'Receiver added.');
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 3) {
            return response()->json([]);
        }
        $me = $request->user('customer');

        return response()->json(
            Customer::where('is_active', true)->where('id', '!=', $me->id)->receiverLookup($q)->limit(10)->get()
                ->map(fn ($c) => ApiReceivers::row($c, masked: true))->values()
        );
    }
}
