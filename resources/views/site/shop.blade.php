@extends('layouts.site')
@section('title', 'Store prices')
@section('content')
<section class="page-hero shop-hero"><div class="container">
    <div class="shop-hero-text">
        <h1>Groceries &amp; store prices</h1>
        <p>Choose these in the app or at the counter when you send shopping. Your family collects them from the store with their ID and code.</p>
    </div>
    <img class="shop-hero-img" src="{{ asset('images/store-shelf.png') }}" width="1200" height="640" alt="Shelves at the MML store with rice, flour, cooking oil, sugar, noodles, corned beef, tinned fish and a solar lantern">
</div></section>
<section class="section"><div class="container stack">
    @if ($stores->isEmpty())
        <div class="empty"><i class="icon-store"></i><strong>No stores listed yet</strong></div>
    @else
        <div class="row-between">
            <div class="segmented" role="tablist" aria-label="Store">
                @foreach ($stores as $s)
                    <a href="{{ route('shop', ['store' => $s->id]) }}" class="btn btn-sm {{ $store->id === $s->id ? 'btn-primary' : 'btn-ghost' }}" style="border:0">{{ $s->name }}</a>
                @endforeach
            </div>
            <form method="get" class="row">
                <input type="hidden" name="store" value="{{ $store->id }}">
                <select name="category" class="select" onchange="this.form.submit()" aria-label="Category">
                    <option value="">All categories</option>
                    @foreach ($categories as $c)<option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>@endforeach
                </select>
            </form>
        </div>
        <p class="muted mb-0"><i class="icon-map-pin"></i> {{ $store->address }}. {{ $store->opening_hours }}</p>
        <div class="products">
            @forelse ($items as $i)
                <div class="product">
                    <div class="p-img">@if($i->image_url)<img src="{{ $i->image_url }}" alt="{{ $i->name }}" loading="lazy" width="800" height="600">@else<i class="icon-package"></i>@endif<span class="tag">{{ $i->category }}</span></div>
                    <div class="p-body">
                        <span class="p-name">{{ $i->name }}</span>
                        <span class="p-price">{{ money($i->price, $i->currency) }} <small>/ {{ $i->unit }}</small></span>
                        <small class="{{ $i->quantity_on_hand ? 'muted' : 'error-text' }}">{{ $i->quantity_on_hand ? 'In stock' : 'Out of stock' }}</small>
                    </div>
                </div>
            @empty
                <div class="empty" style="grid-column:1/-1"><i class="icon-package"></i><strong>Nothing in this category</strong></div>
            @endforelse
        </div>
    @endif
</div></section>
@endsection
