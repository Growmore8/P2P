<?php

use App\Models\Currency;

if (! function_exists('money')) {
    /** Format an amount with its currency symbol, e.g. money(1500, 'INR') → "₹1,500.00". */
    function money($amount, ?string $currency = 'INR', int $decimals = 2): string
    {
        return Currency::symbolFor($currency) . number_format((float) $amount, $decimals);
    }
}

if (! function_exists('asset_v')) {
    /**
     * URL of a file in /public with an automatic version (the file's last-change time),
     * so browsers always load the newest CSS / JS after an update — no manual "?v=" numbers.
     */
    function asset_v(string $path): string
    {
        $file = public_path($path);

        return asset($path) . '?v=' . (is_file($file) ? filemtime($file) : '1');
    }
}
