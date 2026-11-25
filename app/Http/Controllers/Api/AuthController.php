<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Customers sign in with their email OR phone number. */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $login = trim($data['login']);
        $customer = Customer::where('email', $login)->orWhere('phone', $login)->first();

        if (! $customer || ! $customer->password || ! Hash::check($data['password'], $customer->password)) {
            throw ValidationException::withMessages(['login' => 'Those details don\'t match our records.']);
        }
        if (! $customer->is_active || ! $customer->app_access_enabled) {
            throw ValidationException::withMessages(['login' => 'App access is turned off for this account. Please contact your MML office.']);
        }

        $token = $customer->createToken($data['device_name'] ?? 'android')->plainTextToken;

        return response()->json(['token' => $token, 'customer' => self::profile($customer)]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return response()->json(self::profile($request->user()));
    }

    public static function profile(Customer $c): array
    {
        $c->loadMissing('createdByOffice');

        return [
            'id' => $c->id,
            'customer_code' => $c->customer_code,
            'given_name' => $c->given_name,
            'family_name' => $c->family_name,
            'full_name' => $c->full_name,
            'date_of_birth' => $c->date_of_birth?->toDateString(),
            'gender' => $c->gender,
            'residential_address' => $c->residential_address,
            'city' => $c->city,
            'country' => $c->country,
            'phone' => $c->phone,
            'email' => $c->email,
            'photo_url' => $c->photo_url,
            'is_verified' => $c->is_verified,
            'home_office' => $c->createdByOffice?->name,
            'home_currency' => $c->createdByOffice?->currency,
            'unread_messages' => \App\Models\Conversation::unreadForCustomer($c),
        ];
    }
}
