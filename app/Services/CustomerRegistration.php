<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Business rule: new customers are created on the web app only — by a sender office
 * (staff console) or by an existing sender customer (customer portal).
 */
class CustomerRegistration
{
    public static function rules(?Customer $existing = null, bool $withLogin = false): array
    {
        $id = $existing?->id;

        $rules = [
            'given_name' => ['required', 'string', 'max:80'],
            'family_name' => ['required', 'string', 'max:80'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'residential_address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['required', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('customers', 'phone')->ignore($id)],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('customers', 'email')->ignore($id)],
            'id_type' => ['nullable', Rule::in(['passport', 'drivers_licence', 'national_id', 'other'])],
            'id_number' => ['nullable', 'string', 'max:40'],
            'id_expiry' => ['nullable', 'date'],
            'passport_photo' => [$existing ? 'nullable' : 'required', 'image', 'max:5120'],
        ];

        if ($withLogin) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
            $rules['app_access_enabled'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public static function messages(): array
    {
        return [
            'passport_photo.required' => 'A passport-style photo is required so the office can identify the customer.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
        ];
    }

    public function create(Request $request, array $data, array $origin): Customer
    {
        $data['passport_photo_path'] = $request->file('passport_photo')?->store('passports', 'public');
        unset($data['passport_photo'], $data['password_confirmation']);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return Customer::create($data + $origin + [
            'customer_code' => Customer::generateCustomerCode(),
            'app_access_enabled' => true,
            'is_active' => true,
        ]);
    }

    public function update(Request $request, Customer $customer, array $data): Customer
    {
        if ($request->hasFile('passport_photo')) {
            $data['passport_photo_path'] = $request->file('passport_photo')->store('passports', 'public');
        }
        unset($data['passport_photo'], $data['password_confirmation']);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $customer->update($data);

        return $customer;
    }
}
