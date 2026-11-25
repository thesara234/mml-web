@extends('layouts.portal')
@section('title', 'My profile')
@section('content')
<div class="page-head"><div><h1>My profile</h1><p>To change your details, visit your MML office with photo ID, or send us a message.</p></div></div>
<div class="card card-pad" style="max-width:760px">
    <div class="payout-hero">
        <div class="id-photo">@if($me->photo_url)<img src="{{ $me->photo_url }}" alt="">@else{{ $me->initials() }}@endif</div>
        <dl class="dl">
            <dt>Name</dt><dd>{{ $me->full_name }}</dd>
            <dt>Customer code</dt><dd>{{ $me->customer_code }}</dd>
            <dt>Date of birth</dt><dd>{{ $me->date_of_birth?->format('j F Y') }}</dd>
            <dt>Address</dt><dd>{{ $me->residential_address }}, {{ $me->city }}, {{ $me->country }}</dd>
            <dt>Phone</dt><dd>{{ $me->phone ?? '—' }}</dd>
            <dt>Email</dt><dd>{{ $me->email ?? '—' }}</dd>
            <dt>Home office</dt><dd>{{ $me->createdByOffice?->name }}</dd>
            <dt>ID check</dt><dd>@if($me->is_verified)<span class="badge tone-green">Verified</span>@else<span class="badge tone-amber">Not verified yet</span>@endif</dd>
        </dl>
    </div>
</div>
@endsection
