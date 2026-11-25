<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Actor;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user('customer');
        $tab = $request->query('tab', 'sent');
        $with = ['senderCustomer', 'receiverCustomer', 'receiverOffice', 'senderOffice'];

        $q = Transaction::with($with)->latest('transaction_date')->latest('id');
        match ($tab) {
            'drafts' => $q->where('sender_customer_id', $me->id)->where('status', Transaction::DRAFT),
            'incoming' => $q->where('receiver_customer_id', $me->id)->where('status', '!=', Transaction::DRAFT),
            default => $q->where('sender_customer_id', $me->id)->where('status', '!=', Transaction::DRAFT),
        };

        return view('portal.transfers.index', ['transfers' => $q->paginate(15)->withQueryString(), 'tab' => $tab, 'me' => $me]);
    }

    public function show(Request $request, Transaction $transaction)
    {
        $me = $request->user('customer');
        $isSender = $transaction->sender_customer_id === $me->id;
        abort_unless($isSender || ($transaction->receiver_customer_id === $me->id && $transaction->status !== Transaction::DRAFT), 403);

        return view('portal.transfers.show', [
            't' => $transaction->load(['senderCustomer', 'receiverCustomer', 'receiverOffice', 'senderOffice', 'items', 'events']),
            'me' => $me,
            'isSender' => $isSender,
        ]);
    }

    public function cancel(Request $request, Transaction $transaction, TransactionService $service)
    {
        $me = $request->user('customer');
        abort_unless($transaction->sender_customer_id === $me->id, 403);
        $service->cancel($transaction, Actor::customer($me), 'Cancelled by sender on the web portal.');

        return back()->with('success', 'Transfer cancelled.');
    }
}
