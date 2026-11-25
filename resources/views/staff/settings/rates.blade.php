@extends('layouts.staff')
@section('title', 'Exchange rates')
@section('content')
<div class="page-head"><div><h1>Exchange rates</h1><p>New rates apply to transfers quoted from their effective date. Rates already quoted to customers don't change.</p></div></div>
<div class="grid grid-main-side">
    <div class="card">
        <div class="card-head"><h2>Rate history</h2></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Corridor</th><th class="right">Rate</th><th>Effective</th><th>Set by</th></tr></thead>
            <tbody>
            @foreach ($history as $r)
                <tr>
                    <td><span class="flag">{{ currency_flag($r->local_currency) }}</span> {{ $r->local_currency }} <i class="icon-arrow-right muted"></i> <span class="flag">{{ currency_flag($r->foreign_currency) }}</span> {{ $r->foreign_currency }}</td>
                    <td class="right amount">{{ rate_fmt($r->rate) }}</td>
                    <td class="nowrap">{{ $r->effective_date->format('j M Y') }} @if($r->effective_date->isFuture())<span class="badge tone-blue plain">Scheduled</span>@endif</td>
                    <td class="muted small">{{ $r->setByStaff?->fullName() ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @if ($history->hasPages())<div style="border-top:1px solid var(--line-2)">{{ $history->links() }}</div>@endif
    </div>
    <aside class="stack">
        <form method="post" action="{{ route('staff.settings.rates.store') }}" class="card">
            @csrf
            <div class="card-head"><h2>Set a rate</h2></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field"><label for="lc">From</label><select id="lc" name="local_currency" class="select">@foreach ($currencies as $c)<option @selected(old('local_currency', 'NZD') === $c)>{{ $c }}</option>@endforeach</select></div>
                    <div class="field"><label for="fc">To</label><select id="fc" name="foreign_currency" class="select">@foreach ($currencies as $c)<option @selected(old('foreign_currency', 'TOP') === $c)>{{ $c }}</option>@endforeach</select></div>
                </div>
                <div class="field"><label for="rate">1 unit of "From" buys</label><input id="rate" class="input" type="number" step="0.000001" min="0" name="rate" value="{{ old('rate') }}" required placeholder="1.3600"></div>
                <div class="field"><label for="eff">Effective from</label><input id="eff" class="input" type="date" name="effective_date" value="{{ old('effective_date', now()->toDateString()) }}" required></div>
                <label class="check"><input type="checkbox" name="with_inverse" value="1"> Also set the reverse rate (1 ÷ rate)</label>
            </div>
            <div class="card-foot"><button class="btn btn-primary">Save rate</button></div>
        </form>
        <div class="card"><div class="card-head"><h2>Live now</h2></div><div class="card-body" style="padding-top:.3rem;padding-bottom:.3rem">
            <ul class="rate-list">@foreach ($board as $r)<li><span class="pair">{{ $r->local_currency }} <i class="icon-arrow-right muted"></i> {{ $r->foreign_currency }}</span><span class="rate">{{ rate_fmt($r->rate) }}</span></li>@endforeach</ul>
        </div></div>
    </aside>
</div>
@endsection
