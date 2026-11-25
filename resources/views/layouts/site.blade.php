@extends('layouts.base')
@section('body_class', 'site-body')
@section('body')
<header class="site-header">
    <div class="bar">
        <x-brand :href="route('home')" />
        <nav class="site-nav" id="site-nav" aria-label="Main">
            <a href="{{ route('home') }}#send" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Send</a>
            <a href="{{ route('rates') }}" class="{{ request()->routeIs('rates') ? 'is-active' : '' }}">Rates &amp; fees</a>
            <a href="{{ route('shop') }}" class="{{ request()->routeIs('shop') ? 'is-active' : '' }}">Shop prices</a>
            <a href="{{ route('locations') }}" class="{{ request()->routeIs('locations') ? 'is-active' : '' }}">Offices</a>
            <a href="{{ route('track') }}" class="{{ request()->routeIs('track') ? 'is-active' : '' }}">Track a transfer</a>
        </nav>
        <a href="{{ route('portal.login') }}" class="btn btn-primary btn-sm header-cta">Customer sign in</a>
        <button class="btn btn-ghost btn-icon site-menu-btn" data-toggle-nav="#site-nav" aria-label="Menu"><i class="icon-menu"></i></button>
    </div>
</header>
@yield('content')
<footer class="site-footer">
    <div class="container">
        <div class="cols">
            <div>
                <x-brand :href="route('home')" style="color:#fff" />
                <p>Family-owned since 2002. Sending money and shopping home to Tonga from New Zealand and Australia.</p>
                <p class="small"><em>{{ config('mml.motto') }}</em></p>
            </div>
            <div>
                <h4>Send</h4>
                <a href="{{ route('rates') }}">Today's rates</a>
                <a href="{{ route('shop') }}">Store prices</a>
                <a href="{{ route('track') }}">Track a transfer</a>
            </div>
            <div>
                <h4>Visit us</h4>
                <a href="{{ route('locations') }}">Offices &amp; opening hours</a>
                <a href="{{ route('portal.login') }}">Customer sign in</a>
            </div>
            <div>
                <h4>For MML team</h4>
                <a href="{{ route('staff.login') }}">Staff sign in</a>
            </div>
        </div>
        <div class="legal">
            <span>&copy; {{ date('Y') }} Fietokoni &amp; Meafakatonga (NZ) Ltd, trading as Melie Mei Langi.</span>
            <span>Rates change daily. The rate you're quoted is locked when you send.</span>
        </div>
    </div>
</footer>
@endsection
