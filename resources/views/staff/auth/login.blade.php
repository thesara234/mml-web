@extends('layouts.base')
@section('title', 'Staff sign in')
@section('body')
<div class="auth">
    <div class="auth-art">
        <x-brand href="{{ route('home') }}" sub="Office console" style="padding:0" />
        <h1>Mālō e lelei. Ready for the counter?</h1>
        <p>Send for walk-in customers, pay out money and shopping, and answer customer messages, all in one place.</p>
    </div>
    <div class="auth-form">
        <div class="inner">
            <h2>Staff sign in</h2>
            <p class="muted">Use the email your manager set up for you.</p>
            @include('partials.flash')
            <form method="post" action="{{ route('staff.login') }}">
                @csrf
                <div class="field"><label for="email">Email</label><input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
                <div class="field"><label for="password">Password</label><input id="password" class="input" type="password" name="password" required autocomplete="current-password"></div>
                <label class="check" style="margin-bottom:1.2rem"><input type="checkbox" name="remember" value="1"> Keep me signed in on this computer</label>
                <button class="btn btn-primary btn-lg btn-block">Sign in</button>
            </form>
            @if (app()->environment('local'))
                <div class="demo-box">Demo accounts (password <code>password</code>): <code>admin@mmltonga.com</code>, <code>teller.nz@mmltonga.com</code>, <code>store.tonga@mmltonga.com</code></div>
            @endif
            <p class="small muted" style="margin-top:1.4rem">Customer? <a href="{{ route('portal.login') }}">Sign in to My MML</a></p>
        </div>
    </div>
</div>
@endsection
