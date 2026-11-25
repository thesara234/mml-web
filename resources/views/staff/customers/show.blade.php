@extends('layouts.staff')
@section('title', $customer->full_name)
@section('content')
<div class="page-head">
    <div>
        <a href="{{ route('staff.customers.index') }}" class="crumb"><i class="icon-chevron-left"></i>Customers</a>
        <h1>{{ $customer->full_name }}</h1>
        <p>{{ $customer->customer_code }}, registered {{ $customer->created_at->format('j M Y') }} by {{ $customer->registeredByStaff?->fullName() ?? ($customer->registeredByCustomer ? $customer->registeredByCustomer->full_name.' (portal)' : 'MML') }}</p>
    </div>
    <div class="row wrap">
        <form method="post" action="{{ route('staff.messages.start', $customer) }}">@csrf<button class="btn"><i class="icon-message-circle"></i>Message</button></form>
        <a href="{{ route('staff.customers.edit', $customer) }}" class="btn"><i class="icon-pencil"></i>Edit</a>
        <a href="{{ route('staff.send', ['sender' => $customer->id]) }}" class="btn btn-red"><i class="icon-send"></i>Send for {{ $customer->given_name }}</a>
    </div>
</div>
<div class="grid grid-main-side">
    <div class="stack">
        <div class="card card-pad">
            <div class="payout-hero">
                <div class="id-photo">@if($customer->photo_url)<img src="{{ $customer->photo_url }}" alt="Passport photo">@else{{ $customer->initials() }}@endif</div>
                <div>
                    <div class="row wrap gap-s" style="margin-bottom:.8rem">
                        @if ($customer->is_verified)<span class="badge tone-green">ID verified {{ $customer->verified_at?->format('j M Y') }}</span>
                        @else<span class="badge tone-amber">ID not verified</span>@endif
                        @unless ($customer->is_active)<span class="badge tone-red">Inactive</span>@endunless
                        @if ($customer->app_access_enabled && $customer->password)<span class="badge tone-blue plain">Uses app</span>@endif
                    </div>
                    <dl class="dl">
                        <dt>Date of birth</dt><dd>{{ $customer->date_of_birth?->format('j F Y') }} ({{ $customer->age() }})</dd>
                        <dt>Gender</dt><dd>{{ ucfirst($customer->gender) }}</dd>
                        <dt>Address</dt><dd>{{ $customer->residential_address }}{{ $customer->city ? ', '.$customer->city : '' }}, {{ $customer->country }}</dd>
                        <dt>Phone</dt><dd>{{ $customer->phone ?? '—' }}</dd>
                        <dt>Email</dt><dd>{{ $customer->email ?? '—' }}</dd>
                        <dt>Photo ID</dt><dd>{{ $customer->id_type ? str_replace('_', ' ', ucfirst($customer->id_type)).' '.$customer->id_number : 'Not recorded' }}</dd>
                        <dt>Home office</dt><dd>{{ $customer->createdByOffice?->name ?? '—' }}</dd>
                    </dl>
                    @unless ($customer->is_verified)
                        <form method="post" action="{{ route('staff.customers.verify', $customer) }}" style="margin-top:1rem">@csrf
                            <button class="btn btn-lagoon"><i class="icon-badge-check"></i>I've seen their photo ID, mark verified</button>
                        </form>
                    @endunless
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2>Transfers</h2><span class="muted small">Total sent {{ money($totals['sent'], $customer->createdByOffice?->currency) }}</span></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Reference</th><th>Direction</th><th>With</th><th class="right">Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                @forelse ($transactions as $t)
                    @php($out = $t->sender_customer_id === $customer->id)
                    <tr class="is-link" data-href="{{ route('staff.transactions.show', $t) }}">
                        <td><a class="ref" href="{{ route('staff.transactions.show', $t) }}">{{ $t->reference_no }}</a></td>
                        <td><span class="tag"><i class="{{ $out ? 'icon-arrow-up-right' : 'icon-arrow-down-left' }}"></i>{{ $out ? 'Sent' : 'Received' }}</span></td>
                        <td>{{ ($out ? $t->receiverCustomer : $t->senderCustomer)?->full_name }}</td>
                        <td class="right amount">{{ $out ? money($t->transfer_amount, $t->local_currency) : money($t->converted_amount, $t->foreign_currency) }}</td>
                        <td><x-status :t="$t" /></td>
                        <td class="muted small nowrap">{{ $t->transaction_date->format('j M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><i class="icon-arrow-left-right"></i><strong>No transfers yet</strong></div></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
    <aside class="stack">
        <div class="card">
            <div class="card-head"><h2>Receivers</h2><a href="{{ route('staff.customers.create', ['for_sender' => $customer->id]) }}" class="btn btn-sm"><i class="icon-user-plus"></i>New</a></div>
            <ul class="queue">
                @forelse ($customer->receivers as $r)
                    <li><x-person :person="$r" size="sm" :sub="($r->pivot->relationship ? $r->pivot->relationship.', ' : '').$r->city" :href="route('staff.customers.show', $r)" /></li>
                @empty
                    <li class="muted small">No saved receivers.</li>
                @endforelse
            </ul>
        </div>
        @if ($customer->senders->isNotEmpty())
            <div class="card">
                <div class="card-head"><h2>Receives from</h2></div>
                <ul class="queue">@foreach ($customer->senders as $s)<li><x-person :person="$s" size="sm" :sub="$s->pivot->relationship ? 'Their '.strtolower($s->pivot->relationship) : $s->country" :href="route('staff.customers.show', $s)" /></li>@endforeach</ul>
            </div>
        @endif
    </aside>
</div>
@endsection
