@extends('layouts.portal')
@section('title', $t->reference_no)
@section('content')
@php($tone = $t->statusTone())
<div class="page-head">
    <div>
        <a href="{{ route('portal.transfers.index', ['tab' => $isSender ? 'sent' : 'incoming']) }}" class="crumb"><i class="icon-chevron-left"></i>My transfers</a>
        <h1>{{ $isSender ? 'To '.$t->receiverCustomer?->full_name : 'From '.$t->senderCustomer->full_name }}</h1>
        <p>Reference <span class="ref">{{ $t->reference_no }}</span>, {{ $t->transaction_date->format('j F Y') }}</p>
    </div>
    <x-status :t="$t" />
</div>
<div class="grid grid-main-side">
    <div class="stack">
        @if ($isSender && $t->status === 'awaiting_payment')
            <div class="card">
                <div class="card-head"><h2>Pay {{ money($t->total_payable, $t->local_currency) }} to complete your transfer</h2></div>
                <div class="card-body">
                    @if ($t->payment_method === 'internet_banking')
                        <div class="bank-box">
                            <dl class="dl">
                                <dt>Bank</dt><dd>{{ $t->senderOffice?->bank_name }}</dd>
                                <dt>Account name</dt><dd>{{ $t->senderOffice?->bank_account_name }}</dd>
                                <dt>Account number</dt><dd>{{ $t->senderOffice?->bank_account_number }} <button class="copy-btn" data-copy="{{ $t->senderOffice?->bank_account_number }}">Copy</button></dd>
                                <dt>Reference</dt><dd>{{ $t->reference_no }} <button class="copy-btn" data-copy="{{ $t->reference_no }}">Copy</button></dd>
                                <dt>Amount</dt><dd>{{ money($t->total_payable, $t->local_currency) }}</dd>
                            </dl>
                        </div>
                        <p class="muted small" style="margin-top:.8rem">Use the reference exactly, so we can match your payment. The receiver can collect as soon as the office confirms it.</p>
                    @else
                        <p>Pay in cash at <strong>{{ $t->senderOffice?->name }}</strong>, {{ $t->senderOffice?->address }}. Opening hours: {{ $t->senderOffice?->opening_hours }}. Quote reference <strong>{{ $t->reference_no }}</strong>.</p>
                    @endif
                </div>
                <div class="card-foot" style="justify-content:space-between">
                    <span class="muted small">Changed your mind? You can cancel until you've paid.</span>
                    <form method="post" action="{{ route('portal.transfers.cancel', $t) }}" onsubmit="return confirm('Cancel this transfer?')">@csrf<button class="btn btn-danger-ghost btn-sm">Cancel transfer</button></form>
                </div>
            </div>
        @endif

        @if ($t->status === 'ready_for_payout' || ($isSender && $t->isOpen() && $t->payout_code))
            <div class="card card-pad" style="text-align:center">
                <span class="muted">{{ $isSender ? 'Collection code. Share it only with '.$t->receiverCustomer?->given_name : 'Your collection code' }}</span>
                <div class="collect-code" style="margin:.4rem 0">{{ $t->payout_code }}</div>
                <p class="muted small mb-0">{{ $t->status === 'ready_for_payout' ? 'Ready to collect at '.$t->receiverOffice?->name.'. Bring photo ID.' : 'Works once payment is confirmed.' }}</p>
            </div>
        @endif

        @if ($t->status === 'on_hold')
            <div class="alert alert-warn"><i class="icon-circle-pause"></i><div><strong>On hold.</strong> {{ $t->hold_reason }} <a href="{{ route('portal.chat') }}">Message us</a> if you have questions.</div></div>
        @endif

        <div class="card card-pad">
            <div class="summary-line"><span>{{ $isSender ? 'You sent' : 'Sent' }}</span><span>{{ money($t->transfer_amount, $t->local_currency) }}</span></div>
            <div class="summary-line"><span>Rate</span><span>1 {{ $t->local_currency }} = {{ rate_fmt($t->exchange_rate) }} {{ $t->foreign_currency }}</span></div>
            @if ($isSender)
                <div class="summary-line"><span>Service fee</span><span>{{ money($t->service_fee, $t->local_currency) }}</span></div>
                @if ($t->discount > 0)<div class="summary-line"><span>Discount</span><span>− {{ money($t->discount, $t->local_currency) }}</span></div>@endif
                <div class="summary-line"><span><strong>Total</strong></span><span><strong>{{ money($t->total_payable, $t->local_currency) }}</strong></span></div>
            @endif
            <div class="summary-line"><span>{{ $t->transfer_type === 'goods' ? 'Cash with the shopping' : 'Collect in cash' }}</span><span>{{ money($t->cash_payout, $t->foreign_currency) }}</span></div>
            <div class="summary-line"><span>Collect at</span><span>{{ $t->receiverOffice?->name }}</span></div>
        </div>

        @if ($t->items->isNotEmpty())
            <div class="card">
                <div class="card-head"><h2>Shopping</h2><span class="muted">{{ money($t->goods_total, $t->foreign_currency) }}</span></div>
                <ul class="queue">@foreach ($t->items as $i)<li><span class="avatar" style="border-radius:10px"><i class="icon-package"></i></span><div class="grow"><strong>{{ $i->name }}</strong><br><small class="muted">{{ $i->quantity }} {{ $i->unit }} × {{ money($i->unit_price) }}</small></div><span class="amount">{{ money($i->line_total) }}</span></li>@endforeach</ul>
            </div>
        @endif
    </div>
    <aside class="card">
        <div class="card-head"><h2>Progress</h2></div>
        <div class="card-body">
            <ul class="timeline">
                @foreach ($t->events as $e)
                    <li class="tone-{{ event_tone($e->to_status) }}"><span class="dot"></span><strong>{{ \App\Models\Transaction::STATUSES[$e->to_status] ?? $e->to_status }}</strong><time>{{ $e->created_at->format('j M, g:i a') }}</time></li>
                @endforeach
            </ul>
        </div>
    </aside>
</div>
@endsection
