<?php

/*
 | Logos for the homepage scrolling strip.
 | Logo file: public/images/brands/{slug}.svg or .png — if missing, the name is shown as a badge.
 | To add a real logo later (e.g. Trust Wallet), save it as public/images/brands/trustwallet.png.
 |
 | Payment entries have a "show" rule so the site never advertises a method you don't accept:
 |   'upi'  → shown when an active UPI deposit method exists
 |   'bank' → shown when an active bank deposit method exists
 |   any other text → shown only when an active deposit method's title contains it (e.g. "Visa")
 */
return [
    'wallets' => [
        ['name' => 'Binance', 'slug' => 'binance', 'color' => '#F0B90B'],
        ['name' => 'Trust Wallet', 'slug' => 'trustwallet', 'color' => '#3375BB'],
        ['name' => 'Bybit', 'slug' => 'bybit', 'color' => '#F7A600'],
        ['name' => 'OKX', 'slug' => 'okx', 'color' => '#000000'],
        ['name' => 'MetaMask', 'slug' => 'metamask', 'color' => '#E2761B'],
        ['name' => 'KuCoin', 'slug' => 'kucoin', 'color' => '#01BC8D'],
        ['name' => 'Coinbase', 'slug' => 'coinbase', 'color' => '#0052FF'],
        ['name' => 'Bitget', 'slug' => 'bitget', 'color' => '#00C8C8'],
        ['name' => 'SafePal', 'slug' => 'safepal', 'color' => '#4A21EF'],
        ['name' => 'TronLink', 'slug' => 'tronlink', 'color' => '#2B6BEF'],
        ['name' => 'Ledger', 'slug' => 'ledger', 'color' => '#000000'],
        ['name' => 'Tether USDT', 'slug' => 'tether', 'color' => '#50AF95'],
    ],
    'payments' => [
        ['name' => 'UPI', 'slug' => 'upi', 'color' => '#097939', 'show' => 'upi'],
        ['name' => 'Google Pay', 'slug' => 'googlepay', 'color' => '#4285F4', 'show' => 'upi'],
        ['name' => 'PhonePe', 'slug' => 'phonepe', 'color' => '#5F259F', 'show' => 'upi'],
        ['name' => 'Paytm', 'slug' => 'paytm', 'color' => '#00BAF2', 'show' => 'upi'],
        ['name' => 'BHIM', 'slug' => 'bhim', 'color' => '#00A859', 'show' => 'upi'],
        ['name' => 'Bank Transfer', 'slug' => 'bank', 'color' => '#1f56e0', 'show' => 'bank'],
        ['name' => 'Visa', 'slug' => 'visa', 'color' => '#1A1F71', 'show' => 'Visa'],
        ['name' => 'Mastercard', 'slug' => 'mastercard', 'color' => '#EB001B', 'show' => 'Mastercard'],
        ['name' => 'Apple Pay', 'slug' => 'applepay', 'color' => '#000000', 'show' => 'Apple Pay'],
        ['name' => 'PayPal', 'slug' => 'paypal', 'color' => '#003087', 'show' => 'PayPal'],
        ['name' => 'Razorpay', 'slug' => 'razorpay', 'color' => '#0C2451', 'show' => 'Razorpay'],
    ],
];
