@extends('layouts.staff')
@section('title', 'Service fees')
@section('content')
<div class="page-head"><div><h1>Service fees</h1><p>Fees by amount band, charged in the sending currency. Corridors without bands use the standard {{ $defaultPercent }}% fee.</p></div></div>
<div class="grid grid-main-side">
    <div class="stack">
        @forelse ($tiers as $pair => $list)
            <div class="card">
                <div class="card-head"><h2>{{ $pair }}</h2></div>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Amount sent</th><th class="right">Fee</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($list as $f)
                        <tr>
                            <td class="num">{{ $f->local_currency }} {{ $f->label() }}</td>
                            <td class="right amount">{{ $f->describeFee() }}</td>
                            <td class="right"><form method="post" action="{{ route('staff.settings.fees.destroy', $f) }}" onsubmit="return confirm('Remove this fee band?')">@csrf @method('delete')<button class="btn btn-sm btn-ghost" title="Remove"><i class="icon-trash-2"></i><span class="sr-only">Remove</span></button></form></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            </div>
        @empty
            <div class="card"><div class="empty"><i class="icon-receipt"></i><strong>No fee bands yet</strong>Every corridor uses the standard {{ $defaultPercent }}% fee.</div></div>
        @endforelse
    </div>
    <form method="post" action="{{ route('staff.settings.fees.store') }}" class="card">
        @csrf
        <div class="card-head"><h2>Add a fee band</h2></div>
        <div class="card-body">
            <div class="form-grid">
                <div class="field"><label for="lc">From</label><select id="lc" name="local_currency" class="select">@foreach ($currencies as $c)<option @selected($c === 'NZD')>{{ $c }}</option>@endforeach</select></div>
                <div class="field"><label for="fc">To</label><select id="fc" name="foreign_currency" class="select">@foreach ($currencies as $c)<option @selected($c === 'TOP')>{{ $c }}</option>@endforeach</select></div>
                <div class="field"><label for="min">Amount from</label><input id="min" class="input" type="number" step="0.01" min="0" name="min_amount" value="{{ old('min_amount', 0) }}" required></div>
                <div class="field"><label for="max">Amount up to</label><input id="max" class="input" type="number" step="0.01" min="0" name="max_amount" value="{{ old('max_amount') }}" placeholder="No limit"></div>
                <div class="field"><label for="ft">Fee type</label><select id="ft" name="fee_type" class="select"><option value="flat">Flat amount</option><option value="percent">Percentage</option></select></div>
                <div class="field"><label for="fv">Fee</label><input id="fv" class="input" type="number" step="0.01" min="0" name="fee_value" value="{{ old('fee_value') }}" required></div>
            </div>
        </div>
        <div class="card-foot"><button class="btn btn-primary">Add band</button></div>
    </form>
</div>
@endsection
