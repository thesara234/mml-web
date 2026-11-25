@extends('layouts.base')
@section('title', 'Sign in')
@section('body')
<div class="auth">
    <div class="auth-art">
        <x-brand href="{{ route('home') }}" sub="My MML" style="padding:0" />
        <h1>Look after family back home.</h1>
        <p>Send money or shopping, register the people you send to, and see the moment it's collected.</p>
    </div>
    <div class="auth-form">
        <div class="inner">
            <h2>Sign in to My MML</h2>
            <p class="muted">Use the phone number or email your MML office registered for you.</p>
            @include('partials.flash')
            <form method="post" action="{{ route('portal.login') }}">
                @csrf
                <div class="field"><label for="login">Phone or email</label><input id="login" class="input" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"></div>
                <div class="field"><label for="password">Password</label><input id="password" class="input" type="password" name="password" required autocomplete="current-password"></div>
                <label class="check" style="margin-bottom:1.2rem"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                <button class="btn btn-red btn-lg btn-block">Sign in</button>
            </form>
            <p class="small muted" style="margin-top:1.2rem">New to MML? Visit any MML office with your photo ID to open an account. We'll set up your sign-in there.</p>
            <p class="small muted">MML staff? <a href="{{ route('staff.login') }}">Staff sign in</a></p>
            @if (app()->environment('local'))
                <div class="demo-box">Demo: <code>sione.taufa@example.com</code> / <code>password</code> (sender) or <code>mele.taufa@example.com</code> (receiver)</div>
            @endif
        </div>
    </div>
</div>
@endsection
