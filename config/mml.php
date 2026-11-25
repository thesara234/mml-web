<?php

return [
    // Brand
    'brand' => 'Melie Mei Langi',
    'brand_short' => 'MML',
    'tagline' => 'Money & Shopping Transfer',
    'motto' => "Tui pē 'o fai totonu — Trust & do right",

    // Pricing safety rails (fee tiers are managed in the web app)
    'default_fee_percent' => (float) env('MML_DEFAULT_FEE_PERCENT', 3),
    'max_transfer_amount' => (float) env('MML_MAX_TRANSFER_AMOUNT', 10000),

    // Currencies the business works with today. Add more as new countries launch.
    'currencies' => [
        'NZD' => ['name' => 'New Zealand dollar', 'symbol' => '$', 'country_code' => 'NZ'],
        'TOP' => ['name' => "Tongan pa'anga", 'symbol' => 'T$', 'country_code' => 'TO'],
        'AUD' => ['name' => 'Australian dollar', 'symbol' => '$', 'country_code' => 'AU'],
        'USD' => ['name' => 'US dollar', 'symbol' => 'US$', 'country_code' => 'US'],
        'WST' => ['name' => 'Samoan tālā', 'symbol' => 'WS$', 'country_code' => 'WS'],
    ],

    // Chat polling interval used by web + app (milliseconds)
    'chat_poll_ms' => 5000,
];
