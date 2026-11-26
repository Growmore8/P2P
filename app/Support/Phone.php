<?php

namespace App\Support;

class Phone
{
    /**
     * Normalise a typed mobile number to E.164 (+<dial><number>) for the given country.
     * Accepts spaces/dashes, an optional +<dial> prefix and the local trunk "0".
     * Returns null when the number is not valid for that country.
     */
    public static function normalize(string $country, string $raw): ?string
    {
        $c = config("countries.{$country}");
        if (! $c) {
            return null;
        }
        $dial = $c[1];
        $digits = preg_replace('/\D/', '', $raw);
        if (str_starts_with(trim($raw), '+') || str_starts_with($digits, '00' . $dial)) {
            $digits = ltrim($digits, '0');
            if (str_starts_with($digits, $dial)) {
                $digits = substr($digits, strlen($dial));
            }
        }
        $local = ltrim($digits, '0');

        $ok = $country === 'IN'
            ? preg_match('/^[6-9]\d{9}$/', $local)
            : preg_match('/^\d{6,14}$/', $local) && strlen($dial . $local) <= 15;

        return $ok ? '+' . $dial . $local : null;
    }

    public static function error(string $country): string
    {
        return $country === 'IN'
            ? 'Enter a valid 10-digit Indian mobile number.'
            : 'Enter a valid mobile number for ' . config("countries.{$country}.0", 'your country') . '.';
    }
}
