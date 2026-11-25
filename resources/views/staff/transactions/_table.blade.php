<div class="table-wrap">
<table class="table">
    <thead><tr><th>Reference</th><th>Sender</th><th>Receiver</th><th class="right">Amount</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($transactions as $t)
        <tr class="is-link" data-href="{{ route('staff.transactions.show', $t) }}">
            <td class="ref-cell">
                <a href="{{ route('staff.transactions.show', $t) }}" class="ref">{{ $t->reference_no }}</a>
                <small><i class="{{ $t->transfer_type === 'goods' ? 'icon-shopping-basket' : 'icon-banknote' }}"></i> {{ $t->typeLabel() }}, {{ $t->transaction_date->format('j M g:i a') }}</small>
            </td>
            <td><x-person :person="$t->senderCustomer" size="sm" :sub="$t->senderOffice?->code" /></td>
            <td><x-person :person="$t->receiverCustomer" size="sm" :sub="$t->receiverOffice?->code" /></td>
            <td class="right amount amount-cell">{{ money($t->transfer_amount, $t->local_currency) }}<small>{{ money($t->converted_amount, $t->foreign_currency) }}</small></td>
            <td><x-status :t="$t" /></td>
        </tr>
    @empty
        <tr><td colspan="5"><div class="empty"><i class="icon-arrow-left-right"></i><strong>No transactions found</strong>Try a different search or filter.</div></td></tr>
    @endforelse
    </tbody>
</table>
</div>
