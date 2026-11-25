<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Actor;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $service) {}

    public function index(Request $request)
    {
        $staff = $request->user('staff');
        $status = $request->query('status');
        $scope = $staff->isAdmin() ? $request->query('scope', 'office') : 'office';

        $base = Transaction::query()->visibleToStaff()
            ->when($scope === 'office', fn ($q) => $q->forOffice($staff->office_id))
            ->when($request->filled('type'), fn ($q) => $q->where('transfer_type', $request->query('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->query('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim($request->query('q'));
                $q->where(fn ($w) => $w->where('reference_no', 'like', "%{$term}%")
                    ->orWhereHas('senderCustomer', fn ($c) => $c->search($term))
                    ->orWhereHas('receiverCustomer', fn ($c) => $c->search($term)));
            });

        $counts = (clone $base)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $transactions = (clone $base)
            ->with(['senderCustomer', 'receiverCustomer', 'senderOffice', 'receiverOffice'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('transaction_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('staff.transactions.index', compact('transactions', 'counts', 'status', 'scope', 'staff'));
    }

    public function show(Transaction $transaction)
    {
        abort_if($transaction->status === Transaction::DRAFT, 404);
        $transaction->load(['senderCustomer', 'receiverCustomer', 'senderOffice', 'receiverOffice', 'items', 'events', 'createdByStaff', 'completedByStaff']);

        return view('staff.transactions.show', ['t' => $transaction]);
    }

    public function receipt(Transaction $transaction)
    {
        $transaction->load(['senderCustomer', 'receiverCustomer', 'senderOffice', 'receiverOffice', 'items']);

        return view('staff.transactions.receipt', ['t' => $transaction]);
    }

    public function confirmPayment(Request $request, Transaction $transaction)
    {
        $ref = $request->validate(['payment_reference' => ['nullable', 'string', 'max:60']])['payment_reference'] ?? null;
        $this->service->confirmPayment($transaction->load('senderOffice'), $request->user('staff'), $ref);

        return back()->with('success', 'Payment confirmed — the receiver can now collect.');
    }

    public function hold(Request $request, Transaction $transaction)
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:200']])['reason'];
        $this->service->hold($transaction, $request->user('staff'), $reason);

        return back()->with('success', 'Transfer put on hold.');
    }

    public function release(Request $request, Transaction $transaction)
    {
        $this->service->release($transaction, $request->user('staff'), $request->input('remarks'));

        return back()->with('success', 'Hold released.');
    }

    public function cancel(Request $request, Transaction $transaction)
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:200']])['reason'];
        $this->service->cancel($transaction, Actor::staff($request->user('staff')), 'Cancelled by staff: '.$reason);

        return back()->with('success', 'Transfer cancelled.');
    }

    public function refund(Request $request, Transaction $transaction)
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:200']])['reason'];
        $this->service->refund($transaction, $request->user('staff'), $reason);

        return back()->with('success', 'Transfer refunded to the sender.');
    }
}
