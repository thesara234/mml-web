@extends('layouts.staff')
@section('title', 'Messages')
@section('content')
<div class="page-head"><div><h1>Messages</h1><p>Chat with customers. They see your replies in the MML app and web portal.</p></div></div>
<div class="card chat">
    <div class="threads">
        <form method="get" style="padding:.8rem"><input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Find a customer"></form>
        @forelse ($conversations as $c)
            <a class="thread {{ $active && $active->id === $c->id ? 'is-active' : '' }}" href="{{ route('staff.messages.show', $c) }}">
                <x-avatar :person="$c->customer" />
                <div class="grow">
                    <div class="row-between"><strong>{{ $c->customer->full_name }}</strong><small class="muted">{{ $c->last_message_at?->diffForHumans(null, true) }}</small></div>
                    <p>{{ $c->latestMessage?->sender_type === 'staff' ? 'You: ' : '' }}{{ $c->latestMessage?->body ?? 'No messages yet' }}</p>
                </div>
                @if ($c->unread_count)<span class="unread">{{ $c->unread_count }}</span>@endif
            </a>
        @empty
            <div class="empty"><i class="icon-message-circle"></i><strong>No conversations yet</strong>Start one from a customer's profile.</div>
        @endforelse
    </div>
    @if ($active)
        @include('partials.chat', [
            'pollUrl' => route('staff.api.messages.poll', $active),
            'sendUrl' => route('staff.api.messages.send', $active),
            'me' => 'staff',
            'title' => $active->customer->full_name,
            'subtitle' => $active->customer->customer_code.', '.($active->customer->phone ?? $active->customer->country),
            'headerActions' => '<a class="btn btn-sm" href="'.e(route('staff.customers.show', $active->customer)).'">Profile</a>',
        ])
    @else
        <div class="empty" style="align-self:center"><i class="icon-messages-square"></i><strong>Choose a conversation</strong></div>
    @endif
</div>
@endsection
