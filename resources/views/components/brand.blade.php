@props(['href' => '/', 'sub' => null])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'brand']) }}>
    <span class="brand-mark">M</span>
    <span class="brand-text"><strong>{{ config('mml.brand') }}</strong><small>{{ $sub ?? config('mml.tagline') }}</small></span>
</a>
