@extends('layouts.staff')
@section('title', 'Today')
@section('content')
<div class="page-head">
    <div>
        <h1>Mālō e lelei, {{ $staff->given_name }}</h1>
        <p>{{ now()->format('l j F') }} at {{ $office->name }}</p>
    </div>
    <div class="row wrap">
        <a href="{{ route('staff.customers.create') }}" class="btn"><i class="icon-user-plus"></i>New customer</a>
        <a href="{{ route('staff.send') }}" class="btn btn-red"><i class="icon-send"></i>Send money</a>
    </div>
</div>

<div class="stats">
    <a class="stat is-hot" href="{{ route('staff.payout') }}">
        <div class="stat-label"><i class="icon-hand-coins" style="background:rgba(255,255,255,.12);color:#ffb3b9"></i>Ready to collect here</div>
        <div class="stat-value">{{ $stats['ready_here'] }}</div>
        <div class="stat-note">Receivers can collect from {{ $office->code }}</div>
    </a>
    <a class="stat" href="{{ route('staff.transactions.index', ['status' => 'awaiting_payment']) }}">
        <div class="stat-label"><i class="icon-clock" style="background:var(--t-amber-bg);color:var(--t-amber-fg)"></i>Awaiting payment</div>
        <div class="stat-value">{{ $stats['awaiting_payment'] }}</div>
        <div class="stat-note">Check bank deposits and confirm</div>
    </a>
    <a class="stat" href="{{ route('staff.transactions.index', ['status' => 'on_hold']) }}">
        <div class="stat-label"><i class="icon-circle-pause" style="background:var(--t-orange-bg);color:var(--t-orange-fg)"></i>On hold</div>
        <div class="stat-value">{{ $stats['on_hold'] }}</div>
        <div class="stat-note">Need a decision</div>
    </a>
    <a class="stat" href="{{ route('staff.messages.index') }}">
        <div class="stat-label"><i class="icon-message-circle" style="background:var(--moana-100);color:var(--moana-700)"></i>Unread messages</div>
        <div class="stat-value">{{ $stats['unread_messages'] }}</div>
        <div class="stat-note">{{ $stats['completed_today'] }} transfers completed today</div>
    </a>
</div>

<div class="grid grid-main-side" style="margin-top:1.25rem">
    <div class="stack">
        <div class="card">
            <div class="card-head">
                <h2>Paid transfers, last 14 days</h2>
                <span class="muted small">Today {{ money($stats['sent_today_value'], $office->currency) }} taken</span>
            </div>
            <div class="card-body">
                @php($max = max(1, $trend->max('count')))
                <div class="bars" role="img" aria-label="Paid transfers per day for the last 14 days">
                    @foreach ($trend as $d)
                        <div class="bar {{ $d['date']->isToday() ? 'is-today' : '' }}" style="height: {{ max(4, round($d['count'] / $max * 100)) }}%">
                            <span>{{ $d['date']->format('D j M') }}: {{ $d['count'] }} {{ Str::plural('transfer', $d['count']) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="bars-axis">@foreach ($trend as $d)<span>{{ $d['date']->isToday() ? 'Today' : $d['date']->format('j') }}</span>@endforeach</div>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <div class="card-head"><h2>Ready to collect here</h2><a href="{{ route('staff.payout') }}" class="btn btn-sm">Payout desk</a></div>
                <ul class="queue">
                    @forelse ($ready as $t)
                        <li>
                            <x-avatar :person="$t->receiverCustomer" />
                            <div class="grow"><a href="{{ route('staff.payout', ['t' => $t->id]) }}"><strong>{{ $t->receiverCustomer->full_name }}</strong></a><br><small class="muted">{{ $t->typeLabel() }}, from {{ $t->senderCustomer->given_name }}</small></div>
                            <span class="amount">{{ money($t->converted_amount, $t->foreign_currency) }}</span>
                        </li>
                    @empty
                        <li class="empty" style="display:block"><i class="icon-inbox"></i>Nothing waiting to be collected.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card">
                <div class="card-head"><h2>Awaiting payment</h2><a href="{{ route('staff.transactions.index', ['status' => 'awaiting_payment']) }}" class="btn btn-sm">All</a></div>
                <ul class="queue">
                    @forelse ($awaiting as $t)
                        <li>
                            <x-avatar :person="$t->senderCustomer" />
                            <div class="grow"><a href="{{ route('staff.transactions.show', $t) }}"><strong>{{ $t->senderCustomer->full_name }}</strong></a><br><small class="muted">{{ $t->paymentMethodLabel() }}, ref {{ $t->reference_no }}</small></div>
                            <span class="amount">{{ money($t->total_payable, $t->local_currency) }}</span>
                        </li>
                    @empty
                        <li class="empty" style="display:block"><i class="icon-circle-check"></i>All payments are confirmed.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Recent activity</h2><a href="{{ route('staff.transactions.index') }}" class="btn btn-sm">All transactions</a></div>
            @include('staff.transactions._table', ['transactions' => $recent])
        </div>
    </div>

    <aside class="stack">
        <div class="card">
            <div class="card-head"><h2>Today's rates</h2>@if($staff->canManage())<a href="{{ route('staff.settings.rates') }}" class="btn btn-sm">Update</a>@endif</div>
            <div class="card-body" style="padding-top:.4rem;padding-bottom:.4rem">
                <ul class="rate-list">
                    @foreach ($rates as $r)
                        <li><span class="pair"><span class="flag">{{ currency_flag($r->local_currency) }}</span>{{ $r->local_currency }} <i class="icon-arrow-right muted"></i> {{ $r->foreign_currency }}</span><span class="rate">{{ rate_fmt($r->rate) }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="card card-pad">
            <h3>This week</h3>
            <dl class="dl">
                <dt>New customers</dt><dd>{{ $stats['new_customers_week'] }}</dd>
                <dt>Completed today</dt><dd>{{ $stats['completed_today'] }}</dd>
            </dl>
        </div>
    </aside>
</div>
@endsection
