@extends('layouts.staff')
@section('title', $customer->exists ? 'Edit '.$customer->full_name : 'Register customer')
@section('content')
<div class="page-head">
    <div>
        <a href="{{ $customer->exists ? route('staff.customers.show', $customer) : route('staff.customers.index') }}" class="crumb"><i class="icon-chevron-left"></i>{{ $customer->exists ? $customer->full_name : 'Customers' }}</a>
        <h1>{{ $customer->exists ? 'Edit customer' : ($forSender ? 'New receiver for '.$forSender->given_name : 'Register customer') }}</h1>
        <p>{{ $forSender ? "They'll be added to {$forSender->full_name}'s receivers." : 'Check the original photo ID while the customer is at the counter.' }}</p>
    </div>
</div>
<form method="post" enctype="multipart/form-data" action="{{ $customer->exists ? route('staff.customers.update', $customer) : route('staff.customers.store') }}" class="card">
    @csrf
    @if ($customer->exists) @method('put') @endif
    @if ($forSender)<input type="hidden" name="for_sender_id" value="{{ $forSender->id }}">@endif
    <div class="card-body">
        @include('partials.customer-fields')
        @if ($forSender)
            <hr class="divider">
            <div class="form-grid"><div class="field"><label for="rel">Relationship to {{ $forSender->given_name }}</label><input id="rel" class="input" name="relationship" value="{{ old('relationship') }}" placeholder="Mother, brother, friend…"></div></div>
        @endif
        <hr class="divider">
        <h3>App &amp; portal sign-in</h3>
        <p class="muted small">Customers sign in with their phone number or email and this password. Leave blank to keep the current password, or to not give app access yet.</p>
        <div class="form-grid">
            <div class="field"><label for="pw">Password</label><input id="pw" class="input" type="password" name="password" autocomplete="new-password" minlength="8"></div>
            <div class="field"><label for="pw2">Confirm password</label><input id="pw2" class="input" type="password" name="password_confirmation" autocomplete="new-password"></div>
        </div>
        <label class="check"><input type="hidden" name="app_access_enabled" value="0"><input type="checkbox" name="app_access_enabled" value="1" @checked(old('app_access_enabled', $customer->exists ? $customer->app_access_enabled : true))> Allow this customer to use the MML app and web portal</label>
        @if ($customer->exists)
            <label class="check" style="margin-top:.6rem"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active))> Account active</label>
        @endif
    </div>
    <div class="card-foot">
        <a href="{{ url()->previous() }}" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary">{{ $customer->exists ? 'Save changes' : 'Register customer' }}</button>
    </div>
</form>
@endsection
