@props(['person', 'size' => null])
@php($photo = $person?->photo_url ?? null)
<span {{ $attributes->merge(['class' => 'avatar'.($size ? ' avatar-'.$size : '')]) }} aria-hidden="true">
    @if ($photo)<img src="{{ $photo }}" alt="">@else{{ initials($person?->given_name, $person?->family_name) }}@endif
</span>
