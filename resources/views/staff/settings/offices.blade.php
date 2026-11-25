@extends('layouts.staff')
@section('title', 'Offices')
@section('content')
<div class="page-head">
    <div><h1>Offices</h1><p>Sending offices take payments. Receiving offices pay out. Stores hand out shopping. Add a country by adding its offices and a rate.</p></div>
    <button class="btn btn-primary" data-open-dialog="dlg-office-new"><i class="icon-plus"></i>Add office</button>
</div>
<div class="office-cards">
    @foreach ($offices as $o)
        <div class="office-card">
            <div class="row-between"><span class="tag">{{ $o->code }}</span>@unless($o->is_active)<span class="badge tone-neutral">Inactive</span>@endunless</div>
            <h3 style="margin-top:.6rem"><span class="flag">{{ $o->flag() }}</span> {{ $o->name }}</h3>
            <div class="row wrap gap-s" style="margin-bottom:.7rem">
                @if ($o->canSend())<span class="badge tone-blue plain">Send</span>@endif
                @if ($o->canReceive())<span class="badge tone-green plain">Pay out</span>@endif
                @if ($o->has_store)<span class="badge tone-red plain">Store</span>@endif
                <span class="badge plain">{{ $o->currency }}</span>
            </div>
            <div class="o-line"><i class="icon-map-pin"></i>{{ $o->address ?? '—' }}</div>
            <div class="o-line"><i class="icon-clock"></i>{{ $o->opening_hours ?? '—' }}</div>
            <div class="o-line"><i class="icon-users"></i>{{ $o->staff_count }} staff</div>
            @if ($o->bank_account_number)<div class="o-line"><i class="icon-landmark"></i>{{ $o->bank_name }} {{ $o->bank_account_number }}</div>@endif
            <button class="btn btn-sm" style="margin-top:.6rem" data-open-dialog="dlg-office-{{ $o->id }}"><i class="icon-pencil"></i>Edit</button>
        </div>
        <dialog class="modal" id="dlg-office-{{ $o->id }}" style="width:min(640px,calc(100vw - 32px))"><form method="post" action="{{ route('staff.settings.offices.update', $o) }}">@csrf @method('put')
            <div class="modal-body"><h3>{{ $o->name }}</h3>@include('staff.settings._office-fields', ['o' => $o])
                <label class="check" style="margin-bottom:1rem"><input type="checkbox" name="is_active" value="1" @checked($o->is_active)> Office is open for transfers</label></div>
            <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Close</button><button class="btn btn-primary">Save</button></div>
        </form></dialog>
    @endforeach
</div>
<dialog class="modal" id="dlg-office-new" style="width:min(640px,calc(100vw - 32px))"><form method="post" action="{{ route('staff.settings.offices.store') }}">@csrf
    <div class="modal-body"><h3>Add office</h3>@include('staff.settings._office-fields', ['o' => new \App\Models\Office(['office_type' => 'both', 'currency' => 'TOP'])])</div>
    <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Close</button><button class="btn btn-primary">Add office</button></div>
</form></dialog>
@endsection
