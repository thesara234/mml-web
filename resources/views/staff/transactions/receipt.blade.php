@extends('layouts.base')
@section('title', 'Receipt '.$t->reference_no)
@section('body')
<div class="receipt">
    <div class="r-band"></div>
    <div class="row-between"><x-brand href="#" sub="Money & Shopping Transfer" style="padding:0;color:var(--moana-900)" /><x-status :t="$t" /></div>
    <hr class="divider">
    <div class="summary-line"><span>Reference</span><span class="ref">{{ $t->reference_no }}</span></div>
    <div class="summary-line"><span>Date</span><span>{{ $t->transaction_date->format('j M Y, g:i a') }}</span></div>
    <div class="summary-line"><span>Sender</span><span>{{ $t->senderCustomer->full_name }} ({{ $t->senderCustomer->customer_code }})</span></div>
    <div class="summary-line"><span>Receiver</span><span>{{ $t->receiverCustomer->full_name }} ({{ $t->receiverCustomer->customer_code }})</span></div>
    <div class="summary-line"><span>Collect at</span><span>{{ $t->receiverOffice?->name }}</span></div>
    <hr class="divider">
    <div class="summary-line"><span>Amount sent</span><span>{{ money($t->transfer_amount, $t->local_currency) }}</span></div>
    <div class="summary-line"><span>Rate</span><span>1 {{ $t->local_currency }} = {{ rate_fmt($t->exchange_rate) }} {{ $t->foreign_currency }}</span></div>
    <div class="summary-line"><span>Service fee</span><span>{{ money($t->service_fee, $t->local_currency) }}</span></div>
    @if ($t->discount > 0)<div class="summary-line"><span>Discount</span><span>− {{ money($t->discount, $t->local_currency) }}</span></div>@endif
    <div class="summary-line"><span><strong>Total paid</strong></span><span><strong>{{ money($t->total_payable, $t->local_currency) }}</strong></span></div>
    <div class="summary-line"><span>Receiver gets</span><span>{{ money($t->converted_amount, $t->foreign_currency) }}</span></div>
    @foreach ($t->items as $i)<div class="summary-line"><span>{{ $i->quantity }} × {{ $i->name }}</span><span>{{ money($i->line_total) }}</span></div>@endforeach
    @if ($t->transfer_type === 'goods')<div class="summary-line"><span>Cash with shopping</span><span>{{ money($t->cash_payout, $t->foreign_currency) }}</span></div>@endif
    @if ($t->isOpen())
    <div class="panel-soft" style="text-align:center;margin-top:1rem"><span class="small muted">Collection code, give this only to the receiver</span><div class="collect-code">{{ $t->payout_code }}</div></div>
    @endif
    <hr class="divider">
    <p class="small muted">The receiver collects with photo ID and the 6-digit code shown in their MML app (or given to them by the sender). Track at {{ url('/track') }}.</p>
    <p class="small muted mb-0"><em>{{ config('mml.motto') }}</em></p>
    <div class="row no-print" style="margin-top:1rem"><button class="btn btn-primary" onclick="window.print()"><i class="icon-printer"></i>Print</button><a href="{{ url()->previous() }}" class="btn btn-ghost">Back</a></div>
</div>
@endsection
