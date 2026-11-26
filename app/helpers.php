<?php

use App\Models\Currency;

if (! function_exists('money')) {
    /** Format an amount with its currency symbol, e.g. money(1500, 'INR') → "₹1,500.00". */
    function money($amount, ?string $currency = 'INR', int $decimals = 2): string
    {
        return Currency::symbolFor($currency) . number_format((float) $amount, $decimals);
    }
}
