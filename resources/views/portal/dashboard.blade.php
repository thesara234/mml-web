@extends('layouts.portal')
@section('title', 'Home')
@section('content')
<div class="page-head">
    <div><h1 class="display" style="font-size:2rem;margin:0">Mālō e lelei, {{ $me->given_name }}</h1><p>What would you like to do today?</p></div>
    <span class="tag">{{ $me->customer_code }}</span>
</div>

@php($unreadChat = \App\Models\Conversation::unreadForCustomer($me))
@if ($unreadChat)
    <a class="alert alert-info" style="margin-bottom:1rem;text-decoration:none;align-items:center" href="{{ route('portal.chat') }}">
        <i class="icon-message-circle"></i>
        <span><strong>{{ $unreadChat }} new {{ Str::plural('message', $unreadChat) }} from MML staff.</strong> Tap to read and reply.</span>
        <i class="icon-chevron-right" style="margin-left:auto"></i>
    </a>
@endif

<div class="action-tiles">
    <a class="action-tile is-primary" href="{{ route('portal.send') }}">
        <span class="at-icon"><i class="icon-send"></i></span>
        <strong>Send money</strong>
        <small>Money or shopping to family in Tonga</small>
    </a>
    <a class="action-tile" href="{{ route('portal.transfers.index', ['tab' => 'incoming']) }}">
        @if ($incomingMoney)<span class="badge tone-blue at-count">{{ $incomingMoney }} ready</span>@endif
        <span class="at-icon" style="background:var(--lagoon-100);color:var(--lagoon-600)"><i class="icon-banknote"></i></span>
        <strong>Receive money</strong>
        <small>See money sent to you and your collection code</small>
    </a>
    <a class="action-tile" href="{{ route('portal.transfers.index', ['tab' => 'incoming']) }}">
        @if ($incomingGoods)<span class="badge tone-blue at-count">{{ $incomingGoods }} ready</span>@endif
        <span class="at-icon" style="background:var(--sun-100);color:#9a5b00"><i class="icon-shopping-basket"></i></span>
        <strong>Receive shopping</strong>
        <small>Shopping waiting for you at an MML store</small>
    </a>
</div>

<div class="grid grid-main-side" style="margin-top:1.5rem">
    <div class="stack">
        @if ($drafts->isNotEmpty())
            <div class="card">
                <div class="card-head"><h2>Saved for later</h2></div>
                <ul class="queue">
                    @foreach ($drafts as $t)
                        <li>
                            <span class="avatar" style="background:var(--t-neutral-bg);color:var(--ink-3)"><i class="icon-bookmark"></i></span>
                            <div class="grow"><strong>{{ money($t->transfer_amount, $t->local_currency) }} {{ $t->transfer_type === 'goods' ? 'shopping' : '' }}</strong>{{ $t->receiverCustomer ? ' to '.$t->receiverCustomer->given_name : '' }}<br><small class="muted">Saved {{ $t->updated_at->diffForHumans() }}</small></div>
                            <a class="btn btn-sm btn-primary" href="{{ route('portal.send', ['draft' => $t->id]) }}">Continue</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="card">
            <div class="card-head"><h2>Recent transfers</h2><a href="{{ route('portal.transfers.index') }}" class="btn btn-sm">All</a></div>
            <ul class="queue">
                @forelse ($recent as $t)
                    @include('portal._transfer-row')
                @empty
                    <li class="empty" style="display:block"><i class="icon-send"></i><strong>No transfers yet</strong><a href="{{ route('portal.send') }}">Send your first transfer</a></li>
                @endforelse
            </ul>
        </div>
    </div>
    <aside class="stack">
        <div class="card"><div class="card-head"><h2>Today's rates</h2></div><div class="card-body" style="padding-top:.3rem;padding-bottom:.3rem">
            <ul class="rate-list">@foreach ($rates as $r)<li><span class="pair"><span class="flag">{{ currency_flag($r->local_currency) }}</span>{{ $r->local_currency }} <i class="icon-arrow-right muted"></i> {{ $r->foreign_currency }}</span><span class="rate">{{ rate_fmt($r->rate) }}</span></li>@endforeach</ul>
        </div></div>
        <div class="card card-pad">
            <h3>My receivers</h3>
            <p class="muted">{{ $receiverCount }} {{ Str::plural('person', $receiverCount) }} saved.</p>
            <a href="{{ route('portal.receivers.create') }}" class="btn btn-block"><i class="icon-user-plus"></i>Register a new receiver</a>
        </div>
    </aside>
</div>
@endsection
