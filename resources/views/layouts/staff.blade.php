@extends('layouts.base')
@section('body')
@php($me = auth('staff')->user())
@php($unread = \App\Models\Message::where('sender_type', 'customer')->whereNull('read_at')->count())
<div class="shell">
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <x-brand :href="route('staff.dashboard')" sub="Office console" />

        <div class="quick-actions">
            <a href="{{ route('staff.send') }}" class="{{ request()->routeIs('staff.send*') ? 'is-active' : '' }}"><i class="icon-send"></i>Send money</a>
            <a href="{{ route('staff.payout') }}" class="{{ request()->routeIs('staff.payout*') ? 'is-active' : '' }}"><i class="icon-hand-coins"></i>Pay out</a>
        </div>

        <nav class="nav-group">
            <a class="nav-link {{ request()->routeIs('staff.dashboard') ? 'is-active' : '' }}" href="{{ route('staff.dashboard') }}"><i class="icon-layout-dashboard"></i>Today</a>
            <a class="nav-link {{ request()->routeIs('staff.transactions.*') ? 'is-active' : '' }}" href="{{ route('staff.transactions.index') }}"><i class="icon-arrow-left-right"></i>Transactions</a>
            <a class="nav-link {{ request()->routeIs('staff.customers.*') ? 'is-active' : '' }}" href="{{ route('staff.customers.index') }}"><i class="icon-users"></i>Customers</a>
            <a class="nav-link {{ request()->routeIs('staff.messages.*') ? 'is-active' : '' }}" href="{{ route('staff.messages.index') }}"><i class="icon-message-circle"></i>Messages @if($unread)<span class="pill">{{ $unread }}</span>@endif</a>
        </nav>

        @if ($me->canManage())
        <nav class="nav-group">
            <span>Settings</span>
            <a class="nav-link {{ request()->routeIs('staff.settings.rates') ? 'is-active' : '' }}" href="{{ route('staff.settings.rates') }}"><i class="icon-trending-up"></i>Exchange rates</a>
            <a class="nav-link {{ request()->routeIs('staff.settings.fees') ? 'is-active' : '' }}" href="{{ route('staff.settings.fees') }}"><i class="icon-receipt"></i>Service fees</a>
            <a class="nav-link {{ request()->routeIs('staff.settings.stock') ? 'is-active' : '' }}" href="{{ route('staff.settings.stock') }}"><i class="icon-store"></i>Store stock</a>
            <a class="nav-link {{ request()->routeIs('staff.settings.offices') ? 'is-active' : '' }}" href="{{ route('staff.settings.offices') }}"><i class="icon-map-pin"></i>Offices</a>
            <a class="nav-link {{ request()->routeIs('staff.settings.staff') ? 'is-active' : '' }}" href="{{ route('staff.settings.staff') }}"><i class="icon-id-card"></i>Staff accounts</a>
        </nav>
        @endif

        <div class="sidebar-foot">
            <div class="person" style="color:#fff">
                <span class="avatar avatar-sm" style="background:rgba(255,255,255,.12);color:#fff">{{ $me->initials() }}</span>
                <div class="meta"><strong>{{ $me->fullName() }}</strong><small style="color:#8fb1c1">{{ ucfirst($me->role) }}, {{ $me->office->code }}</small></div>
            </div>
        </div>
        <div class="sidebar-band" aria-hidden="true"></div>
    </aside>

    <div>
        <header class="topbar">
            <button class="btn btn-ghost btn-icon mobile-nav-toggle" data-toggle-nav="#sidebar" aria-label="Open menu"><i class="icon-menu"></i></button>
            <form class="search" action="{{ route('staff.transactions.index') }}" method="get" role="search">
                <i class="icon-search"></i>
                <input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Search reference, sender or receiver" aria-label="Search transactions">
            </form>
            <div class="user-menu">
                <span class="office-chip"><span class="flag">{{ $me->office->flag() }}</span>{{ $me->office->name }}</span>
                <form method="post" action="{{ route('staff.logout') }}">@csrf
                    <button class="btn btn-ghost btn-sm" title="Sign out"><i class="icon-log-out"></i><span class="sr-only">Sign out</span></button>
                </form>
            </div>
        </header>
        <main class="main">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@endsection
