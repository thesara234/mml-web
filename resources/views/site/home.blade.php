@extends('layouts.site')
@section('title', 'Send money & shopping to Tonga')
@section('content')
<section class="hero" id="send">
    <div class="container">
        <div>
            <h1>Send home to Tonga: money, or the whole shopping list.</h1>
            <p class="lede">Pay in Auckland or Robinvale, by internet banking or cash. Your family collects the cash, or picks up rice, sipi and groceries from our store in Ma'ufanga or Neiafu.</p>
            <div class="hero-ctas">
                <a href="{{ route('portal.login') }}" class="btn btn-red btn-lg"><i class="icon-send"></i>Send money</a>
                <a href="{{ route('shop') }}" class="btn btn-lg btn-outline-light"><i class="icon-shopping-basket"></i>See store prices</a>
            </div>
            <div class="hero-facts">
                <div><strong>Since 2002</strong>Family-owned and run</div>
                <div><strong>{{ $offices->count() }} offices</strong>in {{ $offices->pluck('country')->unique()->count() }} countries</div>
                <div><strong>Same day</strong>collection once paid</div>
            </div>
        </div>
        @include('partials.calc')
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Two ways to look after family</h2>
            <p>Every transfer is collected in person at an MML office with photo ID and a six-digit code, so it reaches the right person.</p>
        </div>
        <div class="ways">
            <div class="way way-money">
                <h3>Money</h3>
                <p>Your receiver collects cash in pa'anga at the office you choose.</p>
                <ul>
                    <li>Rate locked the moment you send</li>
                    <li>Fee shown before you pay, nothing added later</li>
                    <li>Collect at Ma'ufanga, Neiafu, Pangai or 'Ohonua</li>
                </ul>
                <a href="{{ route('rates') }}" class="btn btn-primary">Today's rates</a>
            </div>
            <div class="way way-shop">
                <h3>Shopping</h3>
                <p>Choose the goods yourself. Family collects them from our store, and any money left over is paid in cash.</p>
                <ul>
                    <li>Rice, flour, sipi, chicken, building supplies</li>
                    <li>Pigs, kie and ngatu for special occasions</li>
                    <li>Prices shown in pa'anga before you send</li>
                </ul>
                <a href="{{ route('shop') }}" class="btn">Browse the store</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head"><h2>How a transfer works</h2></div>
        <ol class="how">
            <li><strong>Enter the amount</strong><p>In NZD, or in pa'anga if you know what they need. You'll see the rate and fee straight away.</p></li>
            <li><strong>Choose money or shopping</strong><p>Pick the receiver from your saved family, then fill the shopping list if you're sending goods.</p></li>
            <li><strong>Pay</strong><p>By internet banking with your reference number, or in cash at the office.</p></li>
            <li><strong>Family collects</strong><p>They bring photo ID and their code from the MML app. You'll see it marked as collected.</p></li>
        </ol>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row-between" style="margin-bottom:1.6rem">
            <div class="section-head" style="margin:0"><h2>Today's rates</h2></div>
            <a href="{{ route('rates') }}" class="btn">Rates &amp; fees</a>
        </div>
        <div class="rate-cards">
            @foreach ($rates as $r)
                <div class="rate-card">
                    <div class="pair"><span class="flag">{{ currency_flag($r->local_currency) }}</span>{{ $r->local_currency }} <i class="icon-arrow-right muted"></i> <span class="flag">{{ currency_flag($r->foreign_currency) }}</span>{{ $r->foreign_currency }}</div>
                    <div class="value">{{ rate_fmt($r->rate) }}</div>
                    <small>1 {{ $r->local_currency }} = {{ rate_fmt($r->rate) }} {{ $r->foreign_currency }}, set {{ $r->effective_date->format('j M') }}</small>
                </div>
            @endforeach
        </div>
    </div>
</section>

@if ($featured->isNotEmpty())
<section class="section section-alt">
    <div class="container">
        <div class="row-between" style="margin-bottom:1.6rem">
            <div class="section-head" style="margin:0"><h2>From the store this week</h2></div>
            <a href="{{ route('shop') }}" class="btn">All prices</a>
        </div>
        <div class="products">
            @foreach ($featured as $i)
                <div class="product">
                    <div class="p-img">@if($i->image_url)<img src="{{ $i->image_url }}" alt="">@else<i class="icon-package"></i>@endif<span class="tag">{{ $i->category }}</span></div>
                    <div class="p-body"><span class="p-name">{{ $i->name }}</span><span class="p-price">{{ money($i->price, $i->currency) }} <small>/ {{ $i->unit }}</small></span><small class="muted">{{ $i->office->name }}</small></div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section">
    <div class="container">
        <div class="app-band">
            <div>
                <h2>The MML app for customers</h2>
                <p>Send money or shopping, save a transfer to finish later, and see when it's collected. Receivers get their collection code in the app, and you can message our team any time.</p>
                <p class="small">Ask at any MML office to set up your login. Accounts are opened in person so we can check your ID.</p>
                <a href="{{ route('portal.login') }}" class="btn btn-red">Sign in on the web</a>
            </div>
            <div class="phone-mock" aria-hidden="true">
                <strong style="font-size:.95rem">Mālō e lelei, Sione</strong>
                <span class="muted">What would you like to do?</span>
                <div class="pm-tile red"><i class="icon-send"></i>Send money</div>
                <div class="pm-tile"><i class="icon-banknote"></i>Receive money</div>
                <div class="pm-tile"><i class="icon-shopping-basket"></i>Receive shopping</div>
                <div style="margin-top:auto;border-radius:12px;background:var(--moana-900);color:#fff;padding:.7rem">
                    <span style="opacity:.7">NZD → TOP</span><br><strong style="font-size:1.2rem">{{ rate_fmt($rates->firstWhere('local_currency', 'NZD')?->rate) }}</strong>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
