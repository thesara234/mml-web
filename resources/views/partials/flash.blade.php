@if (session('success'))
    <div class="alert alert-success mb-1" role="status"><i class="icon-circle-check"></i><div>{{ session('success') }}</div></div>
@endif
@if (session('error'))
    <div class="alert alert-error mb-1" role="alert"><i class="icon-circle-alert"></i><div>{{ session('error') }}</div></div>
@endif
@if ($errors->any())
    <div class="alert alert-error mb-1" role="alert"><i class="icon-circle-alert"></i>
        <div>
            @if ($errors->count() === 1)
                {{ $errors->first() }}
            @else
                Please fix the following:
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            @endif
        </div>
    </div>
@endif
