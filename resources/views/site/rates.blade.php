@extends('layouts.site')
@section('title', 'Exchange rates & fees')
@section('content')
<section class="page-hero"><div class="container">
    <h1>Exchange rates &amp; fees</h1>
    <p>Rates are set each business day. The rate and fee you see when you send are locked for that transfer.</p>
</div></section>
<section class="section"><div class="container">
    <div class="grid grid-main-side">
        <div class="stack">
            <div class="rate-cards">
                @forelse ($rates as $r)
                    <div class="rate-card">
                        <div class="pair"><span class="flag">{{ currency_flag($r->local_currency) }}</span>{{ $r->local_currency }} <i class="icon-arrow-right muted"></i> <span class="flag">{{ currency_flag($r->foreign_currency) }}</span>{{ $r->foreign_currency }}</div>
                        <div class="value">{{ rate_fmt($r->rate) }}</div>
                        <small>Set {{ $r->effective_date->format('l j F Y') }}</small>
                    </div>
                @empty
                    <div class="empty"><i class="icon-trending-up"></i><strong>No rates published yet</strong>Please call your nearest office.</div>
                @endforelse
            </div>
            <div class="card">
                <div class="card-head"><h2>Service fees</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Sending</th><th>Amount sent</th><th class="right">Fee</th></tr></thead>
                        <tbody>
                        @forelse ($fees as $pair => $tiers)
                            @foreach ($tiers as $f)
                                <tr><td>@if($loop->first)<strong>{{ $pair }}</strong>@endif</td><td class="num">{{ $f->local_currency }} {{ $f->label() }}</td><td class="right amount">{{ $f->describeFee() }}</td></tr>
                            @endforeach
                        @empty
                            <tr><td colspan="3" class="muted">A standard fee of {{ config('mml.default_fee_percent') }}% applies.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="sticky-side">@include('partials.calc')</div>
    </div>
</div></section>
@endsection
