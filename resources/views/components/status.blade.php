@props(['t'])
<span {{ $attributes->merge(['class' => 'badge tone-'.$t->statusTone()]) }}>{{ $t->statusLabel() }}</span>
