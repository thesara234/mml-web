<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRules;
use App\Models\Transaction;
use App\Services\Actor;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $service) {}

    private const WITH = ['senderCustomer', 'receiverCustomer', 'senderOffice', 'receiverOffice', 'items'];

    /** ?box=sent (default) | drafts | all */
    public function index(Request $request)
    {
        $me = $request->user();
        $box = $request->string('box', 'sent')->toString();

        $q = Transaction::with(self::WITH)->latest('transaction_date')->latest('id');
        match ($box) {
            'drafts' => $q->where('sender_customer_id', $me->id)->where('status', Transaction::DRAFT),
            'all' => $q->where(fn ($w) => $w->where('sender_customer_id', $me->id)
                ->orWhere(fn ($r) => $r->where('receiver_customer_id', $me->id)->where('status', '!=', Transaction::DRAFT))),
            default => $q->where('sender_customer_id', $me->id)->where('status', '!=', Transaction::DRAFT),
        };

        return response()->json($q->limit(100)->get()->map(fn ($t) => $t->toApiArray($me))->values());
    }

    /**
     * "Receive money" / "Receive shopping" — transfers sent TO me, collected at an MML office.
     * ?type=money|goods  &  ?scope=open (default) | all
     */
    public function incoming(Request $request)
    {
        $me = $request->user();
        $request->validate(['type' => ['nullable', Rule::in(['money', 'goods'])], 'scope' => ['nullable', Rule::in(['open', 'all'])]]);

        $q = Transaction::with(self::WITH)
            ->where('receiver_customer_id', $me->id)
            ->where('status', '!=', Transaction::DRAFT)
            ->when($request->filled('type'), fn ($w) => $w->where('transfer_type', $request->string('type')))
            ->when($request->string('scope', 'open')->toString() === 'open',
                fn ($w) => $w->whereIn('status', [Transaction::AWAITING_PAYMENT, Transaction::READY, Transaction::ON_HOLD]))
            // ready-to-collect first, then newest
            ->orderByRaw("CASE WHEN status = 'ready_for_payout' THEN 0 ELSE 1 END")
            ->latest('transaction_date');

        return response()->json($q->limit(100)->get()->map(fn ($t) => $t->toApiArray($me))->values());
    }

    public function show(Request $request, Transaction $transaction)
    {
        $this->authorizeView($request, $transaction);

        return response()->json($transaction->load([...self::WITH, 'events'])->toApiArray($request->user()));
    }

    /** action=draft → "Save for later";  action=send → "Send / process". */
    public function store(Request $request)
    {
        $data = $request->validate(TransferRules::rules() + ['action' => ['required', Rule::in(['draft', 'send'])]]);
        $me = $request->user();

        $t = $data['action'] === 'draft'
            ? $this->service->saveDraft($me, $data, Actor::customer($me), null, 'mobile_app')
            : $this->service->submit($me, $data, Actor::customer($me), null, 'mobile_app');
        $this->remember($me, $t);

        return response()->json($t->load([...self::WITH, 'events'])->toApiArray($me), 201);
    }

    /** Continue a saved draft. */
    public function update(Request $request, Transaction $transaction)
    {
        $me = $request->user();
        abort_unless($transaction->sender_customer_id === $me->id, 403);
        $data = $request->validate(TransferRules::rules() + ['action' => ['required', Rule::in(['draft', 'send'])]]);

        $t = $data['action'] === 'draft'
            ? $this->service->saveDraft($me, $data, Actor::customer($me), $transaction)
            : $this->service->submit($me, $data, Actor::customer($me), $transaction);
        $this->remember($me, $t);

        return response()->json($t->load([...self::WITH, 'events'])->toApiArray($me));
    }

    /** Senders may cancel before they have paid. */
    public function cancel(Request $request, Transaction $transaction)
    {
        $me = $request->user();
        abort_unless($transaction->sender_customer_id === $me->id, 403);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:200']])['reason'] ?? null;

        $this->service->cancel($transaction, Actor::customer($me), 'Cancelled by sender in the app.'.($reason ? ' '.$reason : ''));

        return response()->json($transaction->fresh([...self::WITH, 'events'])->toApiArray($me));
    }

    public function destroyDraft(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->sender_customer_id === $request->user()->id && $transaction->status === Transaction::DRAFT, 403);
        $transaction->delete();

        return response()->json(['message' => 'Draft deleted.']);
    }

    /** Keep the receiver in the sender's "My receivers" list. */
    private function remember($me, Transaction $t): void
    {
        if ($t->receiver_customer_id) {
            $me->receivers()->syncWithoutDetaching([$t->receiver_customer_id => ['preferred_office_id' => $t->receiver_office_id]]);
        }
    }

    private function authorizeView(Request $request, Transaction $t): void
    {
        $me = $request->user();
        $isSender = $t->sender_customer_id === $me->id;
        $isReceiver = $t->receiver_customer_id === $me->id && $t->status !== Transaction::DRAFT;
        abort_unless($isSender || $isReceiver, 403, 'You can only view your own transfers.');
    }
}
