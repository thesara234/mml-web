<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'staff'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'staff_users'),
    ],

    /*
    | staff    – office / store staff using the web console (/staff)
    | customer – customers using the web portal (/portal)
    | The Android app authenticates customers with Sanctum bearer tokens (auth:sanctum).
    */
    'guards' => [
        'staff' => [
            'driver' => 'session',
            'provider' => 'staff_users',
        ],
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],
    ],

    'providers' => [
        'staff_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\StaffUser::class,
        ],
        'customers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Customer::class,
        ],
    ],

    'passwords' => [
        'staff_users' => [
            'provider' => 'staff_users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
