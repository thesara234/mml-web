@extends('layouts.site')
@section('title', 'Track a transfer')
@section('content')
<section class="page-hero"><div class="container">
    <h1>Track a transfer</h1>
    <p>Enter the reference number from your receipt and the sender's family name.</p>
</div></section>
<section class="section"><div class="container" style="max-width:760px">
    <form method="post" action="{{ route('track') }}" class="card card-pad">
        @csrf
        <div class="form-grid">
            <div class="field"><label for="ref">Reference number</label><input id="ref" class="input" name="reference_no" value="{{ old('reference_no', $t?->reference_no) }}" placeholder="MML261012-ABCDE" required></div>
            <div class="field"><label for="fam">Sender's family name</label><input id="fam" class="input" name="family_name" value="{{ old('family_name') }}" placeholder="Taufa" required></div>
        </div>
        @error('reference_no')<p class="error-text">{{ $message }}</p>@enderror
        <button class="btn btn-primary"><i class="icon-search"></i>Track</button>
    </form>

    @if ($t)
        <div class="card card-pad" style="margin-top:1.25rem">
            <div class="row-between">
                <div><span class="muted small">Reference</span><div class="ref" style="font-size:1.2rem">{{ $t->reference_no }}</div></div>
                <x-status :t="$t" />
            </div>
            <hr class="divider">
            <p class="ink-2">{{ $t->typeLabel() }} transfer to be collected at <strong>{{ $t->receiverOffice?->name }}</strong>.</p>
            <ul class="timeline">
                @foreach ($t->events as $e)
                    <li class="tone-{{ event_tone($e->to_status) }}"><span class="dot"></span><strong>{{ \App\Models\Transaction::STATUSES[$e->to_status] ?? $e->to_status }}</strong><time>{{ $e->created_at->format('D j M Y, g:i a') }}</time></li>
                @endforeach
            </ul>
        </div>
    @endif
</div></section>
@endsection
