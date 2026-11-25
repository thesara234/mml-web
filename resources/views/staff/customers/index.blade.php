@extends('layouts.staff')
@section('title', 'Customers')
@section('content')
<div class="page-head">
    <div><h1>Customers</h1><p>Senders and receivers. New customers can only be registered here or by a sender in the customer portal.</p></div>
    <a href="{{ route('staff.customers.create') }}" class="btn btn-primary"><i class="icon-user-plus"></i>Register customer</a>
</div>
<div class="card">
    <div class="tabs" style="padding:0 1rem">
        <a href="{{ request()->fullUrlWithQuery(['kyc' => null]) }}" class="{{ ! request('kyc') ? 'is-active' : '' }}">Everyone</a>
        <a href="{{ request()->fullUrlWithQuery(['kyc' => 'pending']) }}" class="{{ request('kyc') === 'pending' ? 'is-active' : '' }}">ID to verify <span class="count">{{ $pendingKyc }}</span></a>
        <a href="{{ request()->fullUrlWithQuery(['kyc' => 'verified']) }}" class="{{ request('kyc') === 'verified' ? 'is-active' : '' }}">Verified</a>
    </div>
    <form method="get" class="row" style="padding:1rem 1.4rem;border-bottom:1px solid var(--line-2)">
        <input type="hidden" name="kyc" value="{{ request('kyc') }}">
        <input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Name, customer code, phone or email">
        <button class="btn"><i class="icon-search"></i>Search</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Customer</th><th>Code</th><th>Phone</th><th>Country</th><th class="right">Sent</th><th class="right">Received</th><th>ID</th><th>Registered</th></tr></thead>
        <tbody>
        @forelse ($customers as $c)
            <tr class="is-link" data-href="{{ route('staff.customers.show', $c) }}">
                <td><x-person :person="$c" :sub="$c->email" :href="route('staff.customers.show', $c)" /></td>
                <td class="ref">{{ $c->customer_code }}</td>
                <td class="nowrap">{{ $c->phone ?? '—' }}</td>
                <td>{{ $c->country }}</td>
                <td class="right amount">{{ $c->sent_transactions_count }}</td>
                <td class="right amount">{{ $c->received_transactions_count }}</td>
                <td>@if($c->is_verified)<span class="badge tone-green">Verified</span>@else<span class="badge tone-amber">To check</span>@endif</td>
                <td class="muted small nowrap">{{ $c->created_at->format('j M Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="8"><div class="empty"><i class="icon-users"></i><strong>No customers found</strong><a href="{{ route('staff.customers.create') }}">Register a new customer</a></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($customers->hasPages())<div style="border-top:1px solid var(--line-2)">{{ $customers->links() }}</div>@endif
</div>
@endsection
