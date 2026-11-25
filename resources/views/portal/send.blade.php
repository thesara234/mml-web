@extends('layouts.portal')
@section('title', 'Send money')
@section('content')
<div class="page-head">
    <div><h1>{{ $draft ? 'Finish your transfer' : 'Send money' }}</h1><p>You'll see the rate and fee before you send. You can save it and finish later.</p></div>
</div>
@php
$row = fn ($o) => \App\Models\Transaction::officeArray($o);
$homeCurrency = $me->createdByOffice?->currency;
$cfg = [
    'mode' => 'portal',
    'defaultLocal' => in_array($homeCurrency, $corridors->pluck('local_currency')->all(), true) ? $homeCurrency : 'NZD',
    'corridors' => $corridors->map(fn ($r) => ['local' => $r->local_currency, 'foreign' => $r->foreign_currency])->values(),
    'receivingOffices' => $receivingOffices->map($row)->values(),
    'receivers' => $receivers->values(),
    'preselect' => $preselect,
    'draft' => $draft ? [
        'id' => $draft->id, 'local_currency' => $draft->local_currency, 'foreign_currency' => $draft->foreign_currency,
        'transfer_amount' => $draft->transfer_amount, 'transfer_type' => $draft->transfer_type,
        'receiver_customer_id' => $draft->receiver_customer_id, 'receiver_office_id' => $draft->receiver_office_id,
        'purpose' => $draft->purpose, 'sender_income_source' => $draft->sender_income_source, 'payment_method' => $draft->payment_method,
        'items' => $draft->items->map(fn ($i) => ['stock_item_id' => $i->stock_item_id, 'quantity' => $i->quantity])->values(),
    ] : null,
    'urls' => [
        'quote' => route('portal.api.quote'),
        'receiverSearch' => route('portal.api.receivers.search'),
        'storeItems' => route('portal.api.store-items', ['office' => '__ID__']),
        'submit' => route('portal.send.store'),
        'newReceiver' => route('portal.receivers.create', ['back' => 'send']),
    ],
];
@endphp
@include('partials.send-wizard', ['cfg' => $cfg, 'mode' => 'portal'])
@endsection
