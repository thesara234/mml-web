@extends('layouts.site')
@section('title', 'Offices')
@section('content')
<section class="page-hero"><div class="container">
    <h1>Our offices</h1>
    <p>Send from New Zealand or Australia. Collect money or shopping in Tonga. Bring photo ID every time.</p>
</div></section>
<section class="section"><div class="container stack">
    @foreach ($offices as $country => $list)
        <h2 class="display" style="font-size:1.6rem"><span class="flag">{{ flag($list->first()->country_code) }}</span> {{ $country }}</h2>
        <div class="office-cards">
            @foreach ($list as $o)
                <div class="office-card">
                    <h3>{{ $o->name }}</h3>
                    <div class="row wrap gap-s" style="margin-bottom:.7rem">
                        @if ($o->canSend())<span class="badge tone-blue plain">Send</span>@endif
                        @if ($o->canReceive())<span class="badge tone-green plain">Collect money</span>@endif
                        @if ($o->has_store)<span class="badge tone-red plain">Shopping store</span>@endif
                    </div>
                    @if ($o->address)<div class="o-line"><i class="icon-map-pin"></i><span>{{ $o->address }}</span></div>@endif
                    @if ($o->opening_hours)<div class="o-line"><i class="icon-clock"></i><span>{{ $o->opening_hours }}</span></div>@endif
                    @if ($o->phone)<div class="o-line"><i class="icon-phone"></i><a href="tel:{{ preg_replace('/\s+/', '', $o->phone) }}">{{ $o->phone }}</a></div>@endif
                    @if ($o->email)<div class="o-line"><i class="icon-mail"></i><a href="mailto:{{ $o->email }}">{{ $o->email }}</a></div>@endif
                </div>
            @endforeach
        </div>
    @endforeach
</div></section>
@endsection
