@extends('layouts.staff')
@section('title', $t->reference_no)
@section('content')
<div class="page-head">
    <div>
        <a href="{{ route('staff.transactions.index') }}" class="crumb"><i class="icon-chevron-left"></i>Transactions</a>
        <h1 class="row gap-s wrap"><span>{{ $t->reference_no }}</span> <x-status :t="$t" /></h1>
        <p>{{ $t->typeLabel() }} transfer created {{ $t->transaction_date->format('l j F Y, g:i a') }} via {{ str_replace('_', ' ', $t->initiated_via) }}</p>
    </div>
    <div class="row wrap">
        <a href="{{ route('staff.transactions.receipt', $t) }}" class="btn" target="_blank"><i class="icon-printer"></i>Receipt</a>
        @if ($t->canPayout())<a href="{{ route('staff.payout', ['t' => $t->id]) }}" class="btn btn-lagoon"><i class="icon-hand-coins"></i>Pay out</a>@endif
        @if ($t->canConfirmPayment())<button class="btn btn-primary" data-open-dialog="dlg-pay"><i class="icon-circle-check"></i>Confirm payment</button>@endif
    </div>
</div>

@if ($t->status === 'on_hold')
    <div class="alert alert-warn mb-1"><i class="icon-circle-pause"></i><div><strong>On hold.</strong> {{ $t->hold_reason }}</div></div>
@endif

<div class="grid grid-main-side">
    <div class="stack">
        <div class="grid grid-2">
            <div class="card card-pad">
                <div class="row-between"><h3 class="mb-0">Sender</h3><span class="muted small">{{ $t->senderOffice?->name }}</span></div>
                <x-person :person="$t->senderCustomer" size="lg" :sub="$t->senderCustomer->customer_code.', '.$t->senderCustomer->phone" :href="route('staff.customers.show', $t->senderCustomer)" style="margin-top:.8rem" />
            </div>
            <div class="card card-pad">
                <div class="row-between"><h3 class="mb-0">Receiver</h3><span class="muted small">{{ $t->receiverOffice?->name }}</span></div>
                <x-person :person="$t->receiverCustomer" size="lg" :sub="$t->receiverCustomer->customer_code.', '.$t->receiverCustomer->city" :href="route('staff.customers.show', $t->receiverCustomer)" style="margin-top:.8rem" />
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Money</h2></div>
            <div class="card-body">
                <div class="grid grid-2">
                    <div>
                        <div class="summary-line"><span>Amount sent</span><span>{{ money($t->transfer_amount, $t->local_currency) }}</span></div>
                        <div class="summary-line"><span>Exchange rate</span><span>1 {{ $t->local_currency }} = {{ rate_fmt($t->exchange_rate) }} {{ $t->foreign_currency }}</span></div>
                        <div class="summary-line"><span>Service fee</span><span>{{ money($t->service_fee, $t->local_currency) }}</span></div>
                        <div class="summary-line"><span>Discount</span><span>{{ money($t->discount, $t->local_currency) }}</span></div>
                        <div class="summary-line"><span><strong>Total paid by sender</strong></span><span><strong>{{ money($t->total_payable, $t->local_currency) }}</strong></span></div>
                    </div>
                    <div>
                        <div class="summary-line"><span>Receiver gets</span><span>{{ money($t->converted_amount, $t->foreign_currency) }}</span></div>
                        @if ($t->transfer_type === 'goods')<div class="summary-line"><span>Shopping</span><span>{{ money($t->goods_total, $t->foreign_currency) }}</span></div>@endif
                        <div class="summary-line"><span>Cash payout</span><span>{{ money($t->cash_payout, $t->foreign_currency) }}</span></div>
                        <div class="summary-line"><span>Payment</span><span>{{ $t->paymentMethodLabel() }}, {{ $t->payment_status }}</span></div>
                        <div class="summary-line"><span>Payment reference</span><span>{{ $t->payment_reference ?: '—' }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        @if ($t->items->isNotEmpty())
            <div class="card">
                <div class="card-head"><h2>Shopping list</h2><span class="muted small">{{ $t->receiverOffice?->name }}</span></div>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Item</th><th class="right">Unit price</th><th class="right">Qty</th><th class="right">Total</th></tr></thead>
                    <tbody>@foreach ($t->items as $i)<tr><td>{{ $i->name }}</td><td class="right amount">{{ money($i->unit_price) }}</td><td class="right amount">{{ $i->quantity }} {{ $i->unit }}</td><td class="right amount">{{ money($i->line_total, $t->foreign_currency) }}</td></tr>@endforeach</tbody>
                </table></div>
            </div>
        @endif

        <div class="card card-pad">
            <h3>Compliance</h3>
            <dl class="dl">
                <dt>Purpose</dt><dd>{{ $t->purpose }}</dd>
                <dt>Source of funds</dt><dd>{{ $t->sender_income_source }}</dd>
                <dt>Processed by</dt><dd>{{ $t->createdByStaff?->fullName() ?? 'Customer (self-service)' }}</dd>
                @if ($t->completed_at)<dt>Paid out by</dt><dd>{{ $t->completedByStaff?->fullName() }}, {{ $t->completed_at->format('j M Y, g:i a') }}</dd>@endif
                @if ($t->notes)<dt>Note</dt><dd>{{ $t->notes }}</dd>@endif
            </dl>
        </div>
    </div>

    <aside class="stack">
        <div class="card">
            <div class="card-head"><h2>Actions</h2></div>
            <div class="card-body stack-s">
                @if ($t->canHold())<button class="btn btn-block" data-open-dialog="dlg-hold"><i class="icon-circle-pause"></i>Put on hold</button>@endif
                @if ($t->canRelease())
                    <form method="post" action="{{ route('staff.transactions.release', $t) }}">@csrf<button class="btn btn-block"><i class="icon-circle-play"></i>Release hold</button></form>
                @endif
                @if ($t->canRefund())<button class="btn btn-block btn-danger-ghost" data-open-dialog="dlg-refund"><i class="icon-undo-2"></i>Refund sender</button>@endif
                @if ($t->canCancel())<button class="btn btn-block btn-danger-ghost" data-open-dialog="dlg-cancel"><i class="icon-circle-x"></i>Cancel transfer</button>@endif
                <form method="post" action="{{ route('staff.messages.start', $t->senderCustomer) }}">@csrf<button class="btn btn-block btn-ghost"><i class="icon-message-circle"></i>Message sender</button></form>
                @if ($t->receiverCustomer)
                    <form method="post" action="{{ route('staff.messages.start', $t->receiverCustomer) }}">@csrf<button class="btn btn-block btn-ghost"><i class="icon-message-circle"></i>Message receiver</button></form>
                @endif
                @if (! $t->isOpen())<p class="muted small mb-0">This transfer is closed. No further actions.</p>@endif
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2>History</h2></div>
            <div class="card-body">
                <ul class="timeline">
                    @foreach ($t->events as $e)
                        <li class="tone-{{ event_tone($e->to_status) }}">
                            <span class="dot"></span>
                            <strong>{{ \App\Models\Transaction::STATUSES[$e->to_status] ?? $e->to_status }}</strong>
                            <time>{{ $e->created_at->format('j M Y, g:i a') }}, {{ $e->actor_name }}</time>
                            @if ($e->remarks)<p>{{ $e->remarks }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </aside>
</div>

{{-- Dialogs --}}
<dialog class="modal" id="dlg-pay"><form method="post" action="{{ route('staff.transactions.confirm-payment', $t) }}">@csrf
    <div class="modal-body"><h3>Confirm payment received</h3><p class="muted">{{ money($t->total_payable, $t->local_currency) }} by {{ strtolower($t->paymentMethodLabel()) }}. The receiver will be able to collect straight away.</p>
        <div class="field"><label for="pr">Bank or receipt reference</label><input id="pr" class="input" name="payment_reference" value="{{ $t->payment_reference }}"></div></div>
    <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Close</button><button class="btn btn-primary">Confirm payment</button></div>
</form></dialog>
<dialog class="modal" id="dlg-hold"><form method="post" action="{{ route('staff.transactions.hold', $t) }}">@csrf
    <div class="modal-body"><h3>Put on hold</h3><p class="muted">The receiver won't be able to collect until the hold is released. The sender sees the reason in their app.</p>
        <div class="field"><label for="hr">Reason</label><textarea id="hr" class="textarea" name="reason" required maxlength="200" placeholder="e.g. Waiting for proof of funds"></textarea></div></div>
    <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Close</button><button class="btn btn-primary">Put on hold</button></div>
</form></dialog>
<dialog class="modal" id="dlg-cancel"><form method="post" action="{{ route('staff.transactions.cancel', $t) }}">@csrf
    <div class="modal-body"><h3>Cancel this transfer?</h3><p class="muted">No money has been taken, so nothing needs refunding. This can't be undone.</p>
        <div class="field"><label for="cr">Reason</label><textarea id="cr" class="textarea" name="reason" required maxlength="200"></textarea></div></div>
    <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Keep transfer</button><button class="btn btn-red">Cancel transfer</button></div>
</form></dialog>
<dialog class="modal" id="dlg-refund"><form method="post" action="{{ route('staff.transactions.refund', $t) }}">@csrf
    <div class="modal-body"><h3>Refund {{ money($t->total_payable, $t->local_currency) }}?</h3><p class="muted">Return the money to {{ $t->senderCustomer->full_name }} and close the transfer. The receiver will no longer be able to collect.</p>
        <div class="field"><label for="rr">Reason</label><textarea id="rr" class="textarea" name="reason" required maxlength="200"></textarea></div></div>
    <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Close</button><button class="btn btn-red">Refund sender</button></div>
</form></dialog>
@endsection
