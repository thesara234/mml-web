@extends('layouts.staff')
@section('title', 'Transactions')
@section('content')
<div class="page-head">
    <div><h1>Transactions</h1><p>{{ $scope === 'all' ? 'All offices' : 'Sent from or collected at '.$staff->office->name }}</p></div>
    <a href="{{ route('staff.send') }}" class="btn btn-red"><i class="icon-send"></i>Send money</a>
</div>
@php($statuses = collect(\App\Models\Transaction::STATUSES)->except('draft'))
<div class="card">
    <div class="tabs" style="padding:0 1rem">
        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="{{ ! $status ? 'is-active' : '' }}">All <span class="count">{{ $counts->sum() }}</span></a>
        @foreach ($statuses as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}" class="{{ $status === $key ? 'is-active' : '' }}">{{ $label }} <span class="count">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>
    <form method="get" class="row wrap" style="padding:1rem 1.4rem;border-bottom:1px solid var(--line-2)">
        <input type="hidden" name="status" value="{{ $status }}">
        <input class="input" style="flex:1;min-width:220px" type="search" name="q" value="{{ request('q') }}" placeholder="Reference, sender or receiver">
        <select name="type" class="select" style="width:auto"><option value="">Money &amp; shopping</option><option value="money" @selected(request('type') === 'money')>Money</option><option value="goods" @selected(request('type') === 'goods')>Shopping</option></select>
        <input class="input" style="width:auto" type="date" name="from" value="{{ request('from') }}" aria-label="From date">
        <input class="input" style="width:auto" type="date" name="to" value="{{ request('to') }}" aria-label="To date">
        @if ($staff->isAdmin())
            <select name="scope" class="select" style="width:auto"><option value="office">My office</option><option value="all" @selected($scope === 'all')>All offices</option></select>
        @endif
        <button class="btn"><i class="icon-filter"></i>Filter</button>
    </form>
    @include('staff.transactions._table')
    @if ($transactions->hasPages())<div style="border-top:1px solid var(--line-2)">{{ $transactions->links() }}</div>@endif
</div>
@endsection
