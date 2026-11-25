@php($out = $t->sender_customer_id === $me->id)
@php($other = $out ? $t->receiverCustomer : $t->senderCustomer)
<li>
    <x-avatar :person="$other" />
    <div class="grow">
        <a href="{{ $t->status === 'draft' ? route('portal.send', ['draft' => $t->id]) : route('portal.transfers.show', $t) }}"><strong>{{ $other?->full_name ?? 'Receiver not chosen yet' }}</strong></a><br>
        <small class="muted">{{ $out ? 'To' : 'From' }} {{ $other?->given_name ?? '—' }}, {{ $t->typeLabel() }}, {{ $t->transaction_date->format('j M') }}</small>
    </div>
    <x-status :t="$t" />
    <span class="amount">{{ $out ? money($t->transfer_amount, $t->local_currency) : money($t->converted_amount, $t->foreign_currency) }}</span>
</li>
