@extends('layouts.portal')
@section('title', 'Chat')
@section('content')
<div class="page-head"><div><h1>Chat with MML</h1><p>Our team replies during office hours.</p></div></div>
<div class="card" style="height:calc(100vh - 260px);min-height:460px;overflow:hidden">
    @include('partials.chat', ['pollUrl' => route('portal.api.chat.poll'), 'sendUrl' => route('portal.api.chat.send'), 'me' => 'customer', 'title' => 'MML customer support', 'subtitle' => $conversation->office?->name])
</div>
@endsection
