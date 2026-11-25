<?php

return [
    // The Android app uses bearer tokens only, so no stateful (cookie) domains/guards are needed.
    'stateful' => [],
    'guard' => [],
    'expiration' => env('SANCTUM_TOKEN_EXPIRY_MINUTES', 60 * 24 * 30),
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'mml_'),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
