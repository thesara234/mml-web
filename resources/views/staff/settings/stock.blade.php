@extends('layouts.staff')
@section('title', 'Store stock')
@section('content')
<div class="page-head">
    <div><h1>Store stock</h1><p>Items customers can choose when they send shopping. Stock is taken off when the shopping is handed over.</p></div>
    @if ($store)<button class="btn btn-primary" data-open-dialog="dlg-item"><i class="icon-plus"></i>Add item</button>@endif
</div>
@if ($stores->isEmpty())
    <div class="card"><div class="empty"><i class="icon-store"></i><strong>No stores yet</strong>Mark an office as having a store under Offices.</div></div>
@else
<div class="card">
    <div class="tabs" style="padding:0 1rem">
        @foreach ($stores as $s)<a href="{{ route('staff.settings.stock', ['store' => $s->id]) }}" class="{{ $store?->id === $s->id ? 'is-active' : '' }}">{{ $s->name }}</a>@endforeach
    </div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Item</th><th>Category</th><th class="right">Price ({{ $store?->currency }})</th><th class="right">In stock</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($items as $i)
            <tr>
                <td><div class="person"><span class="avatar" style="border-radius:10px">@if($i->image_url)<img src="{{ $i->image_url }}" alt="">@else<i class="icon-package"></i>@endif</span><div class="meta"><strong>{{ $i->name }}</strong><small>{{ $i->sku }}, per {{ $i->unit }}</small></div></div></td>
                <td>{{ $i->category }}</td>
                <td class="right amount">{{ money($i->price) }}</td>
                <td class="right amount">{{ $i->quantity_on_hand }} @if($i->isLow())<span class="badge tone-orange plain">Low</span>@endif</td>
                <td>@if($i->is_active)<span class="badge tone-green">On sale</span>@else<span class="badge tone-neutral">Hidden</span>@endif</td>
                <td class="right"><button class="btn btn-sm" data-open-dialog="dlg-edit-{{ $i->id }}"><i class="icon-pencil"></i>Edit</button></td>
            </tr>
            <dialog class="modal" id="dlg-edit-{{ $i->id }}"><form method="post" enctype="multipart/form-data" action="{{ route('staff.settings.stock.update', $i) }}">@csrf @method('put')
                <div class="modal-body"><h3>{{ $i->name }}</h3>
                    @include('staff.settings._stock-fields', ['item' => $i])
                    <label class="check" style="margin-bottom:1rem"><input type="checkbox" name="is_active" value="1" @checked($i->is_active)> Show to customers</label>
                </div>
                <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Close</button><button class="btn btn-primary">Save</button></div>
            </form></dialog>
        @empty
            <tr><td colspan="6"><div class="empty"><i class="icon-package"></i><strong>No items in this store</strong></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@if ($store)
<dialog class="modal" id="dlg-item"><form method="post" enctype="multipart/form-data" action="{{ route('staff.settings.stock.store') }}">@csrf
    <input type="hidden" name="office_id" value="{{ $store->id }}">
    <div class="modal-body"><h3>Add item to {{ $store->name }}</h3>@include('staff.settings._stock-fields', ['item' => new \App\Models\StockItem(['unit' => 'each'])])</div>
    <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Close</button><button class="btn btn-primary">Add item</button></div>
</form></dialog>
@endif
@endif
@endsection
