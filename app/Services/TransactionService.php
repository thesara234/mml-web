<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Office;
use App\Models\StaffUser;
use App\Models\StockItem;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single place for every transaction rule, shared by the staff web app,
 * the customer web portal and the mobile API.
 *
 * Lifecycle:
 *   draft ──submit──▶ awaiting_payment ──confirm payment──▶ ready_for_payout ──payout──▶ completed
 *     │                   │      ▲                            │      ▲
 *     └──cancel──▶ cancelled◀────┤ hold/release ─▶ on_hold ◀──┘      │
 *                                 ready/on_hold (paid) ──refund──▶ refunded
 */
class TransactionService
{
    public function __construct(private QuoteService $quotes) {}

    // ─────────────────────────────────────────────────────────────
    // Create / update
    // ─────────────────────────────────────────────────────────────

    /** "Save for later": store whatever the sender has filled in so far. */
    public function saveDraft(Customer $sender, array $data, Actor $actor, ?Transaction $draft = null, string $via = 'mobile_app'): Transaction
    {
        if ($draft && ! $draft->isEditableBySender()) {
            throw ValidationException::withMessages(['status' => 'Only draft transfers can be edited.']);
        }

        return DB::transaction(function () use ($sender, $data, $actor, $draft, $via) {
            $t = $draft ?? new Transaction([
                'reference_no' => Transaction::generateReferenceNo(),
                'sender_customer_id' => $sender->id,
                'status' => Transaction::DRAFT,
                'initiated_via' => $via,
                'payment_status' => 'unpaid',
            ]);

            $this->fill($t, $sender, $data, strict: false);
            $isNew = ! $t->exists;
            $t->save();
            $this->syncItems($t, $data);

            if ($isNew) {
                $this->log($t, null, Transaction::DRAFT, $actor, 'Saved for later.');
            }

            return $t->fresh(['items']);
        });
    }

    /** Sender presses "Send": validate everything, lock the quote and wait for payment. */
    public function submit(Customer $sender, array $data, Actor $actor, ?Transaction $draft = null, string $via = 'mobile_app'): Transaction
    {
        if ($draft && ! $draft->isEditableBySender()) {
            throw ValidationException::withMessages(['status' => 'This transfer has already been sent.']);
        }

        return DB::transaction(function () use ($sender, $data, $actor, $draft, $via) {
            $t = $draft ?? new Transaction([
                'reference_no' => Transaction::generateReferenceNo(),
                'sender_customer_id' => $sender->id,
                'status' => Transaction::DRAFT,
                'initiated_via' => $via,
                'payment_status' => 'unpaid',
            ]);

            $this->fill($t, $sender, $data, strict: true);
            $from = $t->exists ? $t->status : null;
            $t->status = Transaction::AWAITING_PAYMENT;
            $t->payment_reference = $t->reference_no;
            $t->payout_code = Transaction::generatePayoutCode();
            $t->transaction_date = now();
            $t->save();
            $this->syncItems($t, $data);

            $how = $t->payment_method === 'cash'
                ? 'Pay in cash at '.$t->senderOffice?->name.'.'
                : 'Pay by internet banking using reference '.$t->reference_no.'.';
            $this->log($t, $from, Transaction::AWAITING_PAYMENT, $actor, 'Transfer submitted. '.$how);

            return $t->fresh(['items', 'senderOffice', 'receiverOffice', 'senderCustomer', 'receiverCustomer']);
        });
    }

    /**
     * Staff serve a walk-in sender at the counter. When the cash is taken on the spot the
     * transfer goes straight to "ready to collect".
     */
    public function createAtCounter(StaffUser $staff, Customer $sender, array $data, bool $paidNow): Transaction
    {
        $data['sender_office_id'] = $data['sender_office_id'] ?? $staff->office_id;
        $actor = Actor::staff($staff);

        return DB::transaction(function () use ($staff, $sender, $data, $paidNow, $actor) {
            $t = $this->submit($sender, $data, $actor, null, 'web_office');
            $t->created_by_staff_id = $staff->id;
            $t->save();

            if ($paidNow) {
                $this->confirmPayment($t, $staff, $data['payment_reference'] ?? null, 'Paid at the counter.');
            }

            return $t->fresh();
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Staff actions
    // ─────────────────────────────────────────────────────────────

    public function confirmPayment(Transaction $t, StaffUser $staff, ?string $reference = null, ?string $remarks = null): Transaction
    {
        if (! $t->canConfirmPayment()) {
            throw ValidationException::withMessages(['status' => 'Payment can only be confirmed on transfers awaiting payment.']);
        }

        $t->forceFill([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_confirmed_by_staff_id' => $staff->id,
            'payment_reference' => $reference ?: $t->payment_reference,
        ]);
        $this->move($t, Transaction::READY, Actor::staff($staff),
            $remarks ?? 'Payment received ('.$t->paymentMethodLabel().'). Ready for the receiver to collect.');

        return $t;
    }

    public function hold(Transaction $t, StaffUser $staff, string $reason): Transaction
    {
        if (! $t->canHold()) {
            throw ValidationException::withMessages(['status' => 'This transfer cannot be put on hold.']);
        }
        $t->status_before_hold = $t->status;
        $t->hold_reason = $reason;
        $this->move($t, Transaction::ON_HOLD, Actor::staff($staff), 'On hold: '.$reason);

        return $t;
    }

    public function release(Transaction $t, StaffUser $staff, ?string $remarks = null): Transaction
    {
        if (! $t->canRelease()) {
            throw ValidationException::withMessages(['status' => 'This transfer is not on hold.']);
        }
        $back = $t->payment_status === 'paid' ? Transaction::READY : Transaction::AWAITING_PAYMENT;
        $t->hold_reason = null;
        $t->status_before_hold = null;
        $this->move($t, $back, Actor::staff($staff), $remarks ?: 'Hold released.');

        return $t;
    }

    public function cancel(Transaction $t, Actor $actor, ?string $reason = null): Transaction
    {
        if (! $t->canCancel()) {
            throw ValidationException::withMessages([
                'status' => $t->payment_status === 'paid'
                    ? 'This transfer has been paid — refund it instead of cancelling.'
                    : 'This transfer can no longer be cancelled.',
            ]);
        }
        $this->move($t, Transaction::CANCELLED, $actor, $reason ?: 'Cancelled.');

        return $t;
    }

    public function refund(Transaction $t, StaffUser $staff, string $reason): Transaction
    {
        if (! $t->canRefund()) {
            throw ValidationException::withMessages(['status' => 'Only paid transfers that have not been collected can be refunded.']);
        }
        $this->move($t, Transaction::REFUNDED, Actor::staff($staff),
            'Refunded '.$t->local_currency.' '.number_format($t->total_payable, 2).' to sender. '.$reason);
        $t->payment_status = 'refunded';
        $t->save();

        return $t;
    }

    /** Receiver office hands over the cash and/or shopping. */
    public function payout(Transaction $t, StaffUser $staff, string $payoutCode, bool $idChecked): Transaction
    {
        if (! $t->canPayout()) {
            throw ValidationException::withMessages(['status' => 'This transfer is not ready to collect.']);
        }
        if ($t->receiver_office_id !== $staff->office_id && ! $staff->isAdmin()) {
            throw ValidationException::withMessages(['status' => 'This transfer is collected at '.$t->receiverOffice?->name.'.']);
        }
        if (! hash_equals((string) $t->payout_code, trim($payoutCode))) {
            throw ValidationException::withMessages(['payout_code' => 'The collection code does not match.']);
        }
        if (! $idChecked) {
            throw ValidationException::withMessages(['id_checked' => "Please confirm you have checked the receiver's photo ID."]);
        }

        return DB::transaction(function () use ($t, $staff) {
            foreach ($t->items as $line) {
                $stock = StockItem::lockForUpdate()->find($line->stock_item_id);
                if (! $stock || $stock->quantity_on_hand < $line->quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough {$line->name} in stock to hand over (have ".($stock->quantity_on_hand ?? 0).", need {$line->quantity}).",
                    ]);
                }
                $stock->decrement('quantity_on_hand', $line->quantity);
            }

            $t->completed_at = now();
            $t->completed_by_staff_id = $staff->id;

            $parts = [];
            if ($t->goods_total > 0) {
                $parts[] = 'shopping worth '.$t->foreign_currency.' '.number_format($t->goods_total, 2);
            }
            if ($t->cash_payout > 0) {
                $parts[] = $t->foreign_currency.' '.number_format($t->cash_payout, 2).' cash';
            }
            $this->move($t, Transaction::COMPLETED, Actor::staff($staff), 'Collected: '.implode(' + ', $parts).'. ID checked.');

            return $t;
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────

    private function fill(Transaction $t, Customer $sender, array $data, bool $strict): void
    {
        $type = $data['transfer_type'] ?? $t->transfer_type ?? 'money';
        $local = strtoupper($data['local_currency'] ?? $t->local_currency ?? 'NZD');
        $foreign = strtoupper($data['foreign_currency'] ?? $t->foreign_currency ?? 'TOP');
        $mode = ($data['amount_mode'] ?? 'send') === 'receive' ? 'receive' : 'send';
        $amount = (float) ($data['amount'] ?? ($mode === 'send' ? $t->transfer_amount : $t->converted_amount) ?? 0);

        $quote = $this->quotes->quote($amount, $local, $foreign, $mode, (float) ($data['discount'] ?? $t->discount ?? 0));

        // Receiver
        $receiverId = Arr::get($data, 'receiver_customer_id', $t->receiver_customer_id);
        $receiver = $receiverId ? Customer::where('is_active', true)->find($receiverId) : null;
        if ($receiverId && ! $receiver) {
            throw ValidationException::withMessages(['receiver_customer_id' => 'That receiver could not be found.']);
        }
        if ($receiver && $receiver->id === $sender->id) {
            throw ValidationException::withMessages(['receiver_customer_id' => "You can't send a transfer to yourself."]);
        }

        // Offices (fall back to sensible defaults)
        $senderOffice = $this->resolveOffice($data['sender_office_id'] ?? $t->sender_office_id, 'send', $local)
            ?? $this->defaultSenderOffice($sender, $local);
        $receiverOffice = $this->resolveOffice($data['receiver_office_id'] ?? $t->receiver_office_id, 'receive', $foreign)
            ?? ($receiver ? $this->defaultReceiverOffice($sender, $receiver, $foreign, $type === 'goods') : null);

        // Shopping
        $goodsTotal = 0.0;
        $lines = $this->normaliseItems($data, $t);
        if ($type === 'goods') {
            if ($receiverOffice && ! $receiverOffice->has_store) {
                throw ValidationException::withMessages(['receiver_office_id' => $receiverOffice->name.' does not have a store. Choose a store for shopping.']);
            }
            foreach ($lines as $line) {
                $stock = StockItem::find($line['stock_item_id']);
                if (! $stock || ! $stock->is_active) {
                    throw ValidationException::withMessages(['items' => 'One of the selected items is no longer available.']);
                }
                if ($receiverOffice && $stock->office_id !== $receiverOffice->id) {
                    throw ValidationException::withMessages(['items' => "{$stock->name} is not sold at {$receiverOffice->name}."]);
                }
                if ($stock->currency !== $foreign) {
                    throw ValidationException::withMessages(['items' => "{$stock->name} is priced in {$stock->currency}, not {$foreign}."]);
                }
                if ($strict && $stock->quantity_on_hand < $line['quantity']) {
                    throw ValidationException::withMessages(['items' => "Only {$stock->quantity_on_hand} × {$stock->name} left in stock."]);
                }
                $goodsTotal += round($stock->price * $line['quantity'], 2);
            }
            if ($goodsTotal > $quote->convertedAmount + 0.001) {
                throw ValidationException::withMessages([
                    'items' => 'Shopping total '.$foreign.' '.number_format($goodsTotal, 2).' is more than the '
                        .$foreign.' '.number_format($quote->convertedAmount, 2).' being sent. Increase the amount or remove items.',
                ]);
            }
        }

        if ($strict) {
            $errors = [];
            if (! $receiver) {
                $errors['receiver_customer_id'] = 'Please choose who will receive this transfer.';
            }
            if (! $senderOffice) {
                $errors['sender_office_id'] = "No sending office accepts {$local}.";
            }
            if (! $receiverOffice) {
                $errors['receiver_office_id'] = 'Please choose where the receiver will collect.';
            }
            if ($type === 'goods' && empty($lines)) {
                $errors['items'] = 'Add at least one shopping item.';
            }
            foreach (['purpose' => 'Please tell us the purpose of this transfer.',
                'sender_income_source' => 'Please tell us the source of these funds.',
                'payment_method' => 'Please choose how you will pay.'] as $field => $msg) {
                if (blank($data[$field] ?? $t->{$field})) {
                    $errors[$field] = $msg;
                }
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        }

        $t->fill([
            'transaction_date' => $t->transaction_date ?? now(),
            'transfer_type' => $type,
            'receiver_customer_id' => $receiver?->id,
            'sender_office_id' => $senderOffice?->id,
            'receiver_office_id' => $receiverOffice?->id,
            'local_currency' => $quote->localCurrency,
            'foreign_currency' => $quote->foreignCurrency,
            'exchange_rate_id' => $quote->rateId,
            'exchange_rate' => $quote->rate,
            'transfer_amount' => $quote->transferAmount,
            'converted_amount' => $quote->convertedAmount,
            'service_fee' => $quote->serviceFee,
            'discount' => $quote->discount,
            'total_payable' => $quote->totalPayable,
            'goods_total' => round($goodsTotal, 2),
            'cash_payout' => round($quote->convertedAmount - $goodsTotal, 2),
            'purpose' => $data['purpose'] ?? $t->purpose,
            'sender_income_source' => $data['sender_income_source'] ?? $t->sender_income_source,
            'payment_method' => $data['payment_method'] ?? $t->payment_method,
            'notes' => $data['notes'] ?? $t->notes,
        ]);

        // keep normalised lines for syncItems()
        $this->pendingLines = $type === 'goods' ? $lines : [];
    }

    /** @var array<int, array{stock_item_id:int, quantity:int}> */
    private array $pendingLines = [];

    private function syncItems(Transaction $t, array $data): void
    {
        $t->items()->delete();
        foreach ($this->pendingLines as $line) {
            $stock = StockItem::find($line['stock_item_id']);
            $t->items()->create([
                'stock_item_id' => $stock->id,
                'name' => $stock->name,
                'unit' => $stock->unit,
                'quantity' => $line['quantity'],
                'unit_price' => $stock->price,
                'line_total' => round($stock->price * $line['quantity'], 2),
            ]);
        }
        $this->pendingLines = [];
    }

    private function normaliseItems(array $data, Transaction $t): array
    {
        if (! array_key_exists('items', $data)) {
            return $t->exists
                ? $t->items->map(fn ($i) => ['stock_item_id' => $i->stock_item_id, 'quantity' => $i->quantity])->all()
                : [];
        }

        // merge duplicate lines and drop zero quantities
        $merged = [];
        foreach ((array) $data['items'] as $row) {
            $id = (int) ($row['stock_item_id'] ?? 0);
            $qty = (int) ($row['quantity'] ?? 0);
            if ($id > 0 && $qty > 0) {
                $merged[$id] = ($merged[$id] ?? 0) + $qty;
            }
        }

        return collect($merged)->map(fn ($q, $id) => ['stock_item_id' => $id, 'quantity' => $q])->values()->all();
    }

    private function resolveOffice($id, string $role, string $currency): ?Office
    {
        if (! $id) {
            return null;
        }
        $office = Office::active()->find($id);
        if (! $office) {
            throw ValidationException::withMessages(["{$role}_office" => 'That office is not available.']);
        }
        $field = $role === 'send' ? 'sender_office_id' : 'receiver_office_id';
        if ($role === 'send' && ! $office->canSend()) {
            throw ValidationException::withMessages([$field => "{$office->name} does not accept transfers."]);
        }
        if ($role === 'receive' && ! $office->canReceive()) {
            throw ValidationException::withMessages([$field => "{$office->name} does not pay out transfers."]);
        }
        if ($office->currency !== $currency) {
            throw ValidationException::withMessages([$field => "{$office->name} works in {$office->currency}, not {$currency}."]);
        }

        return $office;
    }

    private function defaultSenderOffice(Customer $sender, string $local): ?Office
    {
        $home = $sender->createdByOffice;
        if ($home && $home->is_active && $home->canSend() && $home->currency === $local) {
            return $home;
        }

        return Office::active()->sending()->where('currency', $local)->orderBy('id')->first();
    }

    private function defaultReceiverOffice(Customer $sender, Customer $receiver, string $foreign, bool $needsStore): ?Office
    {
        $preferredId = $sender->receivers()->where('receiver_customer_id', $receiver->id)->first()?->pivot?->preferred_office_id;
        $q = Office::active()->receiving()->where('currency', $foreign);
        if ($needsStore) {
            $q->where('has_store', true);
        }
        if ($preferredId && ($o = (clone $q)->find($preferredId))) {
            return $o;
        }

        return $q->orderBy('id')->first();
    }

    private function move(Transaction $t, string $to, Actor $actor, ?string $remarks): void
    {
        if (! $t->canMoveTo($to)) {
            throw ValidationException::withMessages(['status' => "Can't move from {$t->statusLabel()} to ".(Transaction::STATUSES[$to] ?? $to).'.']);
        }
        $from = $t->status;
        $t->status = $to;
        $t->save();
        $this->log($t, $from, $to, $actor, $remarks);
    }

    private function log(Transaction $t, ?string $from, string $to, Actor $actor, ?string $remarks): void
    {
        $t->events()->create([
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'remarks' => $remarks,
        ]);
    }
}
