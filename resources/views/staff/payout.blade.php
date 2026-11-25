@extends('layouts.staff')
@section('title', 'Pay out')
@section('content')
<div class="page-head">
    <div><h1>Pay out</h1><p>Hand over money or shopping at {{ $staff->office->name }}. Always check photo ID against the photo on file.</p></div>
</div>

<div class="grid grid-main-side">
    <div class="stack">
        <form method="get" class="card card-pad">
            <label for="pq" class="label">Find the transfer</label>
            <div class="row" style="margin-top:.35rem">
                <input id="pq" class="input" name="q" value="{{ $q }}" placeholder="Reference number, receiver's name or phone" autofocus>
                <button class="btn btn-primary"><i class="icon-search"></i>Find</button>
            </div>
        </form>

        @if ($q !== '' && ! $selected)
            <div class="card">
                <div class="card-head"><h2>{{ $results->count() }} {{ Str::plural('match', $results->count()) }} for “{{ $q }}”</h2></div>
                <ul class="queue">
                    @forelse ($results as $t)
                        <li>
                            <x-avatar :person="$t->receiverCustomer" />
                            <div class="grow"><a href="{{ route('staff.payout', ['q' => $q, 't' => $t->id]) }}"><strong>{{ $t->receiverCustomer->full_name }}</strong></a><br><small class="muted">{{ $t->reference_no }}, {{ $t->typeLabel() }}</small></div>
                            <x-status :t="$t" />
                            <span class="amount">{{ money($t->converted_amount, $t->foreign_currency) }}</span>
                        </li>
                    @empty
                        <li class="empty" style="display:block"><i class="icon-search-x"></i><strong>No transfer found</strong>Check the reference, or that it's collected at this office.</li>
                    @endforelse
                </ul>
            </div>
        @endif

        @if ($selected)
            @php($t = $selected)
            <div class="card">
                <div class="card-head">
                    <div><span class="ref">{{ $t->reference_no }}</span> <x-status :t="$t" style="margin-left:.4rem" /></div>
                    <a href="{{ route('staff.transactions.show', $t) }}" class="btn btn-sm">Full details</a>
                </div>
                <div class="card-body">
                    <div class="payout-hero">
                        <div class="id-photo">@if($t->receiverCustomer->photo_url)<img src="{{ $t->receiverCustomer->photo_url }}" alt="Photo on file for {{ $t->receiverCustomer->full_name }}">@else{{ $t->receiverCustomer->initials() }}@endif</div>
                        <div class="stack-s">
                            <div>
                                <h2 class="mb-0">{{ $t->receiverCustomer->full_name }}</h2>
                                <span class="muted">{{ $t->receiverCustomer->customer_code }}, born {{ $t->receiverCustomer->date_of_birth?->format('j M Y') }}</span>
                                @unless ($t->receiverCustomer->is_verified)<span class="badge tone-amber plain" style="margin-left:.3rem">ID not verified yet</span>@endunless
                            </div>
                            <dl class="dl">
                                <dt>From</dt><dd>{{ $t->senderCustomer->full_name }}, {{ $t->senderOffice?->name }}</dd>
                                <dt>Address</dt><dd>{{ $t->receiverCustomer->residential_address }}</dd>
                                <dt>ID on file</dt><dd>{{ $t->receiverCustomer->id_type ? str_replace('_', ' ', ucfirst($t->receiverCustomer->id_type)) : 'Not recorded' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <hr class="divider">
                    @if ($t->transfer_type === 'goods')
                        <h3><i class="icon-shopping-basket"></i> Shopping to hand over</h3>
                        <div class="table-wrap"><table class="table">
                            <thead><tr><th>Item</th><th class="right">Qty</th><th class="right">Total</th></tr></thead>
                            <tbody>
                                @foreach ($t->items as $i)
                                    <tr><td>{{ $i->name }}</td><td class="right amount">{{ $i->quantity }} {{ $i->unit }}</td><td class="right amount">{{ money($i->line_total, $t->foreign_currency) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    @endif
                    <div class="row-between" style="margin-top:1rem">
                        <div><span class="muted">{{ $t->transfer_type === 'goods' ? 'Cash to hand over with the shopping' : 'Cash to hand over' }}</span><div class="collect-amount">{{ money($t->cash_payout, $t->foreign_currency) }}</div></div>
                        @if ($t->transfer_type === 'goods')<div class="right"><span class="muted">Shopping value</span><div class="amount" style="font-size:1.2rem">{{ money($t->goods_total, $t->foreign_currency) }}</div></div>@endif
                    </div>
                </div>

                @if ($t->canPayout())
                    <form method="post" action="{{ route('staff.payout.complete', $t) }}" class="card-foot" style="justify-content:space-between;align-items:flex-end">
                        @csrf
                        <div class="field mb-0" style="width:220px">
                            <label for="code">Collection code from the receiver</label>
                            <input id="code" class="input code-input" name="payout_code" inputmode="numeric" maxlength="6" autocomplete="off" required placeholder="••••••">
                        </div>
                        <label class="check" style="max-width:320px"><input type="checkbox" name="id_checked" value="1" required><span>I've checked their photo ID and it matches the photo on file.</span></label>
                        <button class="btn btn-lagoon btn-lg"><i class="icon-hand-coins"></i>Hand over &amp; complete</button>
                    </form>
                @else
                    <div class="card-foot" style="justify-content:flex-start">
                        <div class="alert alert-warn" style="width:100%"><i class="icon-info"></i>
                            <div>
                                @if ($t->status === 'awaiting_payment') This transfer hasn't been paid yet, so it can't be collected.
                                @elseif ($t->status === 'on_hold') On hold: {{ $t->hold_reason }}
                                @elseif ($t->status === 'completed') Already collected on {{ $t->completed_at?->format('j M Y, g:i a') }}.
                                @else This transfer is {{ strtolower($t->statusLabel()) }}.
                                @endif
                                @if ($t->receiver_office_id !== $staff->office_id) It's collected at {{ $t->receiverOffice?->name }}. @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <aside class="card">
        <div class="card-head"><h2>Waiting at this office</h2><span class="badge tone-blue plain">{{ $queue->count() }}</span></div>
        <ul class="queue">
            @forelse ($queue as $t)
                <li>
                    <x-avatar :person="$t->receiverCustomer" size="sm" />
                    <div class="grow"><a href="{{ route('staff.payout', ['t' => $t->id]) }}"><strong>{{ $t->receiverCustomer->full_name }}</strong></a><br><small class="muted">{{ $t->typeLabel() }}, paid {{ $t->paid_at?->diffForHumans() }}</small></div>
                    <span class="amount small">{{ money($t->converted_amount, $t->foreign_currency) }}</span>
                </li>
            @empty
                <li class="empty" style="display:block"><i class="icon-inbox"></i>No one is waiting to collect.</li>
            @endforelse
        </ul>
    </aside>
</div>
@endsection
