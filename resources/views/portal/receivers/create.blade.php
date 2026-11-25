@extends('layouts.portal')
@section('title', 'Register a receiver')
@section('content')
@php($customer = new \App\Models\Customer(['country' => 'Tonga']))
<div class="page-head">
    <div>
        <a href="{{ route('portal.receivers.index') }}" class="crumb"><i class="icon-chevron-left"></i>My receivers</a>
        <h1>Register a new receiver</h1>
        <p>Use their details exactly as they appear on their photo ID. The office checks the ID and this photo when they collect.</p>
    </div>
</div>
<div class="alert alert-info mb-1"><i class="icon-info"></i><div>Already registered with MML? Search for them on the send screen instead, so they don't end up with two accounts.</div></div>
<form method="post" enctype="multipart/form-data" action="{{ route('portal.receivers.store') }}" class="card">
    @csrf
    <input type="hidden" name="back" value="{{ request('back') }}">
    <div class="card-body">
        @include('partials.customer-fields')
        <hr class="divider">
        <div class="form-grid">
            <div class="field"><label for="rel">They are my…</label><input id="rel" class="input" name="relationship" value="{{ old('relationship') }}" required placeholder="Mother, brother, aunty, friend" list="rels"></div>
            <div class="field"><label for="pref">Usually collects at</label>
                <select id="pref" class="select" name="preferred_office_id"><option value="">Choose later</option>@foreach ($offices as $o)<option value="{{ $o->id }}" @selected(old('preferred_office_id') == $o->id)>{{ $o->name }}</option>@endforeach</select>
            </div>
        </div>
        <datalist id="rels"><option>Mother</option><option>Father</option><option>Brother</option><option>Sister</option><option>Son</option><option>Daughter</option><option>Aunty</option><option>Uncle</option><option>Grandparent</option><option>Cousin</option><option>Friend</option></datalist>
    </div>
    <div class="card-foot">
        <a href="{{ route('portal.receivers.index') }}" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary"><i class="icon-user-plus"></i>Register receiver</button>
    </div>
</form>
@endsection
