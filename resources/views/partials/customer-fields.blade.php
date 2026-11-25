{{-- Customer details shared by staff registration and the portal "new receiver" form. Needs $customer --}}
<div class="photo-form" x-data="{ preview: @js($customer->photo_url) }">
    <div>
        <span class="label">Passport photo</span>
        <label class="id-photo" style="margin-top:.35rem;cursor:pointer;width:100%" for="passport_photo">
            <template x-if="preview"><img :src="preview" alt="Photo preview"></template>
            <template x-if="!preview"><span class="stack-s" style="text-align:center;font-size:.85rem;font-weight:600"><i class="icon-camera" style="font-size:2rem"></i><span>Add photo</span></span></template>
        </label>
        <input id="passport_photo" type="file" name="passport_photo" accept="image/*" capture="user" class="sr-only" @change="const f = $event.target.files[0]; if (f) preview = URL.createObjectURL(f)">
        <p class="hint" style="margin-top:.4rem">Face clearly visible, like a passport photo. Used to check ID at collection.</p>
        @error('passport_photo')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <div>
        <div class="form-grid">
            <div class="field"><label for="given_name">Given name</label><input id="given_name" class="input @error('given_name') is-invalid @enderror" name="given_name" value="{{ old('given_name', $customer->given_name) }}" required></div>
            <div class="field"><label for="family_name">Family name</label><input id="family_name" class="input @error('family_name') is-invalid @enderror" name="family_name" value="{{ old('family_name', $customer->family_name) }}" required></div>
            <div class="field"><label for="dob">Date of birth</label><input id="dob" class="input @error('date_of_birth') is-invalid @enderror" type="date" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth?->toDateString()) }}" required max="{{ now()->subDay()->toDateString() }}"></div>
            <div class="field"><span class="label">Gender</span>
                <div class="segmented" role="radiogroup">
                    @foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $k => $v)
                        <label><input type="radio" name="gender" value="{{ $k }}" @checked(old('gender', $customer->gender) === $k) required><span>{{ $v }}</span></label>
                    @endforeach
                </div>
            </div>
            <div class="field span-2"><label for="addr">Residential address</label><input id="addr" class="input @error('residential_address') is-invalid @enderror" name="residential_address" value="{{ old('residential_address', $customer->residential_address) }}" required placeholder="Street and village / suburb"></div>
            <div class="field"><label for="city">Town / city</label><input id="city" class="input" name="city" value="{{ old('city', $customer->city) }}"></div>
            <div class="field"><label for="country">Country</label>
                <select id="country" class="select" name="country" required>
                    @foreach (['Tonga', 'New Zealand', 'Australia', 'United States', 'Samoa', 'Fiji'] as $c)<option @selected(old('country', $customer->country) === $c)>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div class="field"><label for="phone">Mobile phone</label><input id="phone" class="input @error('phone') is-invalid @enderror" type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" placeholder="+676 …"></div>
            <div class="field"><label for="email">Email <span class="muted">(optional)</span></label><input id="email" class="input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $customer->email) }}"></div>
            <div class="field"><label for="id_type">Photo ID type</label>
                <select id="id_type" class="select" name="id_type">
                    <option value="">Not recorded</option>
                    @foreach (['passport' => 'Passport', 'drivers_licence' => "Driver's licence", 'national_id' => 'National ID', 'other' => 'Other'] as $k => $v)<option value="{{ $k }}" @selected(old('id_type', $customer->id_type) === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="field"><label for="id_number">ID number</label><input id="id_number" class="input" name="id_number" value="{{ old('id_number', $customer->id_number) }}"></div>
        </div>
    </div>
</div>
