<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;

/** Receiver office: hand over money and/or shopping. */
class PayoutController extends Controller
{
    public function index(Request $request)
    {
        $staff = $request->user('staff');
        $q = trim((string) $request->query('q', ''));
        $with = ['senderCustomer', 'receiverCustomer', 'senderOffice', 'receiverOffice', 'items'];

        $results = collect();
        if ($q !== '') {
            $results = Transaction::with($with)
                ->visibleToStaff()
                ->where(function ($w) use ($q) {
                    $w->where('reference_no', 'like', "%{$q}%")
                        ->orWhereHas('receiverCustomer', fn ($c) => $c->search($q));
                })
                ->when(! $staff->isAdmin(), fn ($w) => $w->where('receiver_office_id', $staff->office_id))
                ->orderByRaw("CASE WHEN status = 'ready_for_payout' THEN 0 ELSE 1 END")
                ->latest('transaction_date')->limit(20)->get();
        }

        $queue = Transaction::with($with)->where('receiver_office_id', $staff->office_id)
            ->where('status', Transaction::READY)->oldest('paid_at')->limit(30)->get();

        $selected = $request->filled('t') ? Transaction::with($with)->find($request->integer('t')) : ($results->count() === 1 ? $results->first() : null);

        return view('staff.payout', compact('q', 'results', 'queue', 'selected', 'staff'));
    }

    public function complete(Request $request, Transaction $transaction, TransactionService $service)
    {
        $data = $request->validate([
            'payout_code' => ['required', 'string', 'max:8'],
            'id_checked' => ['accepted'],
        ], ['id_checked.accepted' => "Please confirm you have checked the receiver's photo ID."]);

        $service->payout($transaction->load('items', 'receiverOffice'), $request->user('staff'), $data['payout_code'], true);

        return redirect()->route('staff.transactions.show', $transaction)
            ->with('success', "Paid out {$transaction->reference_no} to {$transaction->receiverCustomer->full_name}.");
    }
}
