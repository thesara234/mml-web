<div class="form-grid">
    <div class="field"><label>Code</label><input class="input" name="code" value="{{ $o->code }}" required placeholder="TBU-KIN"></div>
    <div class="field"><label>Type</label><select class="select" name="office_type">@foreach (['both' => 'Send & pay out', 'sender' => 'Send only', 'receiver' => 'Pay out only'] as $k => $v)<option value="{{ $k }}" @selected($o->office_type === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="field span-2"><label>Name</label><input class="input" name="name" value="{{ $o->name }}" required></div>
    <div class="field"><label>Country</label><input class="input" name="country" value="{{ $o->country }}" required></div>
    <div class="field"><label>Country code</label><input class="input" name="country_code" value="{{ $o->country_code }}" maxlength="2" required placeholder="TO"></div>
    <div class="field"><label>Town / city</label><input class="input" name="city" value="{{ $o->city }}"></div>
    <div class="field"><label>Currency</label><select class="select" name="currency">@foreach (array_keys(config('mml.currencies')) as $c)<option @selected($o->currency === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="field span-2"><label>Address</label><input class="input" name="address" value="{{ $o->address }}"></div>
    <div class="field"><label>Phone</label><input class="input" name="phone" value="{{ $o->phone }}"></div>
    <div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ $o->email }}"></div>
    <div class="field span-2"><label>Opening hours</label><input class="input" name="opening_hours" value="{{ $o->opening_hours }}" placeholder="Mon–Fri 8:30am–5pm, Sat 9am–1pm"></div>
    <div class="field"><label>Bank</label><input class="input" name="bank_name" value="{{ $o->bank_name }}"></div>
    <div class="field"><label>Account name</label><input class="input" name="bank_account_name" value="{{ $o->bank_account_name }}"></div>
    <div class="field span-2"><label>Account number <span class="muted">(shown to customers paying by internet banking)</span></label><input class="input" name="bank_account_number" value="{{ $o->bank_account_number }}"></div>
</div>
<label class="check" style="margin-bottom:.6rem"><input type="checkbox" name="has_store" value="1" @checked($o->has_store)> This office has a store for shopping transfers</label>
