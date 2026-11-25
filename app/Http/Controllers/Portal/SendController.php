<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Api\ReceiverController as ApiReceivers;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRules;
use App\Models\ExchangeRate;
use App\Models\Office;
use App\Models\Transaction;
use App\Services\Actor;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SendController extends Controller
{
    public function create(Request $request)
    {
        $me = $request->user('customer');
        $draft = $request->filled('draft')
            ? Transaction::with('items')->where('sender_customer_id', $me->id)->where('status', Transaction::DRAFT)->find($request->integer('draft'))
            : null;

        $receivers = $me->receivers()->where('customers.is_active', true)->orderBy('given_name')->get()
            ->map(fn ($c) => ApiReceivers::row($c) + ['relationship' => $c->pivot->relationship, 'preferred_office_id' => $c->pivot->preferred_office_id]);

        return view('portal.send', [
            'me' => $me,
            'draft' => $draft,
            'receivers' => $receivers,
            'sendingOffices' => Office::active()->sending()->orderBy('name')->get(),
            'receivingOffices' => Office::active()->receiving()->orderBy('name')->get(),
            'corridors' => ExchangeRate::board(),
            'preselect' => $request->integer('receiver') ?: null,
        ]);
    }

    public function store(Request $request, TransactionService $service)
    {
        $me = $request->user('customer');
        $data = $request->validate(TransferRules::rules() + [
            'action' => ['required', Rule::in(['draft', 'send'])],
            'draft_id' => ['nullable', 'integer'],
        ]);
        $draft = ! empty($data['draft_id'])
            ? Transaction::where('sender_customer_id', $me->id)->where('status', Transaction::DRAFT)->findOrFail($data['draft_id'])
            : null;

        $t = $data['action'] === 'draft'
            ? $service->saveDraft($me, $data, Actor::customer($me), $draft, 'web_customer')
            : $service->submit($me, $data, Actor::customer($me), $draft, 'web_customer');

        if ($t->receiver_customer_id) {
            $me->receivers()->syncWithoutDetaching([$t->receiver_customer_id => ['preferred_office_id' => $t->receiver_office_id]]);
        }

        session()->flash('success', $data['action'] === 'draft'
            ? 'Saved for later. You can finish it any time from “My transfers”.'
            : "Transfer {$t->reference_no} sent. Please complete your payment.");

        return response()->json(['redirect' => route('portal.transfers.show', $t)]);
    }
}
