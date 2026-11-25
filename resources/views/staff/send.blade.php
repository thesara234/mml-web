@extends('layouts.staff')
@section('title', 'Send money')
@section('content')
<div class="page-head">
    <div><h1>Send money</h1><p>Serve a walk-in sender. Money or shopping, paid at the counter or by internet banking.</p></div>
</div>
@php
$row = fn ($o) => \App\Models\Transaction::officeArray($o);
$cfg = [
    'mode' => 'staff',
    'sender' => $sender ? \App\Http\Controllers\Api\ReceiverController::row($sender) + ['is_verified' => $sender->is_verified] : null,
    'defaultLocal' => $staff->office->canSend() ? $staff->office->currency : 'NZD',
    'corridors' => $corridors->map(fn ($r) => ['local' => $r->local_currency, 'foreign' => $r->foreign_currency])->values(),
    'receivingOffices' => $receivingOffices->map($row)->values(),
    'receivers' => [],
    'urls' => [
        'quote' => route('staff.api.quote'),
        'customerSearch' => route('staff.api.customers.search'),
        'senderReceivers' => route('staff.api.customers.receivers', ['customer' => '__ID__']),
        'receiverSearch' => route('staff.api.customers.search'),
        'storeItems' => route('staff.api.store-items', ['office' => '__ID__']),
        'submit' => route('staff.send.store'),
        'newReceiver' => route('staff.customers.create', ['for_sender' => '__ID__']),
    ],
];
@endphp
@include('partials.send-wizard', ['cfg' => $cfg, 'mode' => 'staff'])
@endsection
