@extends('layouts.portal')
@section('title', 'My transfers')
@section('content')
<div class="page-head">
    <div><h1>My transfers</h1><p>Everything you've sent, saved for later, or are receiving.</p></div>
    <a href="{{ route('portal.send') }}" class="btn btn-red"><i class="icon-send"></i>Send money</a>
</div>
<div class="card">
    <div class="tabs" style="padding:0 1rem">
        <a href="{{ route('portal.transfers.index', ['tab' => 'sent']) }}" class="{{ $tab === 'sent' ? 'is-active' : '' }}"><i class="icon-arrow-up-right"></i>Sent</a>
        <a href="{{ route('portal.transfers.index', ['tab' => 'incoming']) }}" class="{{ $tab === 'incoming' ? 'is-active' : '' }}"><i class="icon-arrow-down-left"></i>Receiving</a>
        <a href="{{ route('portal.transfers.index', ['tab' => 'drafts']) }}" class="{{ $tab === 'drafts' ? 'is-active' : '' }}"><i class="icon-bookmark"></i>Saved for later</a>
    </div>
    <ul class="queue">
        @forelse ($transfers as $t)
            @include('portal._transfer-row')
        @empty
            <li class="empty" style="display:block"><i class="icon-inbox"></i><strong>Nothing here yet</strong></li>
        @endforelse
    </ul>
    @if ($transfers->hasPages())<div style="border-top:1px solid var(--line-2)">{{ $transfers->links() }}</div>@endif
</div>
@endsection
