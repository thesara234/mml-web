@extends('layouts.base')
@section('body')
@php($me = auth('customer')->user())
@php($unreadChat = request()->routeIs('portal.chat') ? 0 : \App\Models\Conversation::unreadForCustomer($me))
<header class="portal-top">
    <div class="bar">
        <x-brand :href="route('portal.dashboard')" sub="My MML" style="padding:0" />
        <nav aria-label="Portal">
            <a href="{{ route('portal.dashboard') }}" class="{{ request()->routeIs('portal.dashboard') ? 'is-active' : '' }}"><i class="icon-house"></i>Home</a>
            <a href="{{ route('portal.send') }}" class="{{ request()->routeIs('portal.send*') ? 'is-active' : '' }}"><i class="icon-send"></i>Send</a>
            <a href="{{ route('portal.transfers.index') }}" class="{{ request()->routeIs('portal.transfers.*') ? 'is-active' : '' }}"><i class="icon-history"></i>My transfers</a>
            <a href="{{ route('portal.receivers.index') }}" class="{{ request()->routeIs('portal.receivers.*') ? 'is-active' : '' }}"><i class="icon-users"></i>Receivers</a>
            <a href="{{ route('portal.chat') }}" class="{{ request()->routeIs('portal.chat') ? 'is-active' : '' }}"><i class="icon-message-circle"></i>Chat @if($unreadChat)<span class="pill" aria-label="{{ $unreadChat }} new">{{ $unreadChat }}</span>@endif</a>
        </nav>
        <div class="row" style="margin-left:auto">
            <a href="{{ route('portal.profile') }}" class="person" style="color:#fff" title="My profile">
                <x-avatar :person="$me" size="sm" style="background:rgba(255,255,255,.14);color:#fff" />
            </a>
            <form method="post" action="{{ route('portal.logout') }}">@csrf
                <button class="btn btn-sm" style="--bg:transparent;--fg:#cfe0e8;--bd:rgba(255,255,255,.2)">Sign out</button>
            </form>
        </div>
    </div>
    <div class="band" aria-hidden="true"></div>
</header>
<main class="portal-main">
    @include('partials.flash')
    @yield('content')
</main>
@endsection
