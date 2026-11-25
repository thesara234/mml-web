@extends('layouts.portal')
@section('title', 'My receivers')
@section('content')
<div class="page-head">
    <div><h1>My receivers</h1><p>The people you send to. They show photo ID matching their registration when they collect.</p></div>
    <a href="{{ route('portal.receivers.create') }}" class="btn btn-primary"><i class="icon-user-plus"></i>Register new receiver</a>
</div>
<div class="office-cards">
    @forelse ($receivers as $r)
        <div class="office-card">
            <x-person :person="$r" size="lg" :sub="($r->pivot->relationship ? $r->pivot->relationship.', ' : '').($r->city ?: $r->country)" />
            <hr class="divider">
            <div class="o-line"><i class="icon-id-card"></i>{{ $r->customer_code }} @if($r->is_verified)<span class="badge tone-green plain">Verified</span>@endif</div>
            @if ($r->phone)<div class="o-line"><i class="icon-phone"></i>{{ $r->phone }}</div>@endif
            @if ($r->pivot->preferred_office_id && ($po = $offices->get($r->pivot->preferred_office_id)))<div class="o-line"><i class="icon-map-pin"></i>Usually collects at {{ $po->name }}</div>@endif
            <a href="{{ route('portal.send', ['receiver' => $r->id]) }}" class="btn btn-sm btn-red" style="margin-top:.6rem"><i class="icon-send"></i>Send to {{ $r->given_name }}</a>
        </div>
    @empty
        <div class="card" style="grid-column:1/-1"><div class="empty"><i class="icon-users"></i><strong>No receivers yet</strong>Register the first person you'd like to send to.</div></div>
    @endforelse
</div>
@endsection
