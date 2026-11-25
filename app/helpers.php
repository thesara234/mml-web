<?php

if (! function_exists('money')) {
    /** "NZD 1,250.00" — currency code first so senders never confuse NZD with T$. */
    function money(float|int|string|null $amount, ?string $currency = null, int $decimals = 2): string
    {
        $v = number_format((float) $amount, $decimals);

        return $currency ? "{$currency} {$v}" : $v;
    }
}

if (! function_exists('rate_fmt')) {
    function rate_fmt(float|string|null $rate): string
    {
        return rtrim(rtrim(number_format((float) $rate, 4), '0'), '.');
    }
}

if (! function_exists('initials')) {
    function initials(?string $given, ?string $family = null): string
    {
        $g = ltrim((string) $given, "'ʻ‘");
        $f = ltrim((string) $family, "'ʻ‘");

        return mb_strtoupper(mb_substr($g, 0, 1).mb_substr($f, 0, 1));
    }
}

if (! function_exists('flag')) {
    function flag(?string $countryCode): string
    {
        $cc = strtoupper((string) $countryCode);
        if (strlen($cc) !== 2) {
            return '🏳️';
        }

        return mb_chr(0x1F1E6 + ord($cc[0]) - 65).mb_chr(0x1F1E6 + ord($cc[1]) - 65);
    }
}

if (! function_exists('currency_flag')) {
    function currency_flag(?string $ccy): string
    {
        return flag(config('mml.currencies.'.strtoupper((string) $ccy).'.country_code'));
    }
}

if (! function_exists('event_tone')) {
    function event_tone(string $status): string
    {
        return (new App\Models\Transaction(['status' => $status]))->statusTone();
    }
}
