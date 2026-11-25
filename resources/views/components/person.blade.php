@props(['person', 'sub' => null, 'size' => null, 'href' => null])
<div {{ $attributes->merge(['class' => 'person']) }}>
    <x-avatar :person="$person" :size="$size" />
    <div class="meta">
        @if ($href)<a href="{{ $href }}"><strong>{{ $person?->full_name ?? '—' }}</strong></a>@else<strong>{{ $person?->full_name ?? '—' }}</strong>@endif
        @if ($sub)<small>{{ $sub }}</small>@endif
    </div>
</div>
