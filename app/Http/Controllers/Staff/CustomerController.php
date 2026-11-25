<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Api\ReceiverController;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Office;
use App\Models\Transaction;
use App\Services\CustomerRegistration;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with('createdByOffice')
            ->when($request->filled('q'), fn ($q) => $q->search($request->query('q')))
            ->when($request->query('kyc') === 'pending', fn ($q) => $q->where('is_verified', false))
            ->when($request->query('kyc') === 'verified', fn ($q) => $q->where('is_verified', true))
            ->withCount(['sentTransactions', 'receivedTransactions'])
            ->latest()->paginate(20)->withQueryString();

        return view('staff.customers.index', [
            'customers' => $customers,
            'pendingKyc' => Customer::where('is_verified', false)->count(),
        ]);
    }

    public function create(Request $request)
    {
        $forSender = $request->filled('for_sender') ? Customer::find($request->integer('for_sender')) : null;

        return view('staff.customers.form', [
            'customer' => new Customer(['country' => $forSender ? 'Tonga' : 'New Zealand']),
            'forSender' => $forSender,
            'offices' => Office::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CustomerRegistration $registration)
    {
        $data = $request->validate(CustomerRegistration::rules(withLogin: true) + [
            'for_sender_id' => ['nullable', 'integer', 'exists:customers,id'],
            'relationship' => ['nullable', 'string', 'max:40'],
        ], CustomerRegistration::messages());

        $staff = $request->user('staff');
        $forSender = $data['for_sender_id'] ?? null;
        $relationship = $data['relationship'] ?? null;
        unset($data['for_sender_id'], $data['relationship']);

        $customer = $registration->create($request, $data, [
            'created_by_office_id' => $staff->office_id,
            'registered_by_staff_id' => $staff->id,
            'app_access_enabled' => $request->boolean('app_access_enabled', true),
        ]);

        if ($forSender) {
            Customer::find($forSender)->receivers()->syncWithoutDetaching([$customer->id => ['relationship' => $relationship]]);

            return redirect()->route('staff.customers.show', $forSender)
                ->with('success', "{$customer->full_name} registered and added as a receiver.");
        }

        return redirect()->route('staff.customers.show', $customer)->with('success', "Customer {$customer->customer_code} registered.");
    }

    public function show(Customer $customer)
    {
        $customer->load(['createdByOffice', 'registeredByStaff', 'registeredByCustomer', 'receivers', 'senders']);
        $transactions = Transaction::with(['senderCustomer', 'receiverCustomer', 'receiverOffice'])
            ->visibleToStaff()
            ->where(fn ($q) => $q->where('sender_customer_id', $customer->id)->orWhere('receiver_customer_id', $customer->id))
            ->latest('transaction_date')->limit(25)->get();

        $totals = [
            'sent' => (float) Transaction::where('sender_customer_id', $customer->id)->where('payment_status', 'paid')->sum('transfer_amount'),
            'count' => $transactions->count(),
        ];

        return view('staff.customers.show', compact('customer', 'transactions', 'totals'));
    }

    public function edit(Customer $customer)
    {
        return view('staff.customers.form', [
            'customer' => $customer,
            'forSender' => null,
            'offices' => Office::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer, CustomerRegistration $registration)
    {
        $data = $request->validate(CustomerRegistration::rules($customer, withLogin: true) + [
            'is_active' => ['nullable', 'boolean'],
        ], CustomerRegistration::messages());
        $data['app_access_enabled'] = $request->boolean('app_access_enabled');
        $data['is_active'] = $request->boolean('is_active');

        $registration->update($request, $customer, $data);

        return redirect()->route('staff.customers.show', $customer)->with('success', 'Customer details updated.');
    }

    public function verify(Request $request, Customer $customer)
    {
        $customer->update(['is_verified' => true, 'verified_at' => now(), 'verified_by_staff_id' => $request->user('staff')->id]);

        return back()->with('success', "{$customer->full_name}'s identity is verified.");
    }

    public function linkReceiver(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'receiver_customer_id' => ['required', 'integer', 'exists:customers,id'],
            'relationship' => ['nullable', 'string', 'max:40'],
        ]);
        abort_if((int) $data['receiver_customer_id'] === $customer->id, 422);
        $customer->receivers()->syncWithoutDetaching([$data['receiver_customer_id'] => ['relationship' => $data['relationship'] ?? null]]);

        return back()->with('success', 'Receiver linked.');
    }

    /** JSON for the send screen's customer pickers. */
    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Customer::where('is_active', true)->search($term)->orderBy('given_name')->limit(12)->get()
                ->map(fn ($c) => ReceiverController::row($c) + ['email' => $c->email])->values()
        );
    }

    public function receiversJson(Customer $customer)
    {
        return response()->json(
            $customer->receivers()->where('customers.is_active', true)->orderBy('given_name')->get()
                ->map(fn ($c) => ReceiverController::row($c) + [
                    'relationship' => $c->pivot->relationship,
                    'preferred_office_id' => $c->pivot->preferred_office_id,
                ])->values()
        );
    }
}
