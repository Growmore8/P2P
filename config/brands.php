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
        // A payment link (e.g. Razorpay) accepts UPI apps, cards and net banking, so those logos show with it.
        ['name' => 'UPI', 'slug' => 'upi', 'color' => '#097939', 'show' => ['upi', 'link']],
        ['name' => 'Google Pay', 'slug' => 'googlepay', 'color' => '#4285F4', 'show' => ['upi', 'link']],
        ['name' => 'PhonePe', 'slug' => 'phonepe', 'color' => '#5F259F', 'show' => ['upi', 'link']],
        ['name' => 'Paytm', 'slug' => 'paytm', 'color' => '#00BAF2', 'show' => ['upi', 'link']],
        ['name' => 'BHIM', 'slug' => 'bhim', 'color' => '#00A859', 'show' => ['upi', 'link']],
        ['name' => 'Visa', 'slug' => 'visa', 'color' => '#1A1F71', 'show' => ['link', 'Visa', 'card']],
        ['name' => 'Mastercard', 'slug' => 'mastercard', 'color' => '#EB001B', 'show' => ['link', 'Mastercard', 'card']],
        ['name' => 'Net banking', 'slug' => 'bank', 'color' => '#1f56e0', 'show' => ['bank', 'link']],
        ['name' => 'Apple Pay', 'slug' => 'applepay', 'color' => '#000000', 'show' => 'Apple Pay'],
        ['name' => 'PayPal', 'slug' => 'paypal', 'color' => '#003087', 'show' => 'PayPal'],
        ['name' => 'Razorpay', 'slug' => 'razorpay', 'color' => '#0C2451', 'show' => 'Razorpay'],
        ['name' => 'American Express', 'slug' => 'americanexpress', 'color' => '#2E77BC', 'show' => ['Amex', 'American Express']],
        ['name' => 'Samsung Pay', 'slug' => 'samsungpay', 'color' => '#1428A0', 'show' => ['Samsung Pay']],
        ['name' => 'Amazon Pay', 'slug' => 'amazonpay', 'color' => '#FF9900', 'show' => ['Amazon Pay']],
        ['name' => 'Alipay', 'slug' => 'alipay', 'color' => '#1677FF', 'show' => ['Alipay']],
        ['name' => 'WeChat Pay', 'slug' => 'wechat', 'color' => '#07C160', 'show' => ['WeChat']],
        ['name' => 'Wise', 'slug' => 'wise', 'color' => '#163300', 'show' => ['Wise']],
        ['name' => 'Revolut', 'slug' => 'revolut', 'color' => '#000000', 'show' => ['Revolut']],
        ['name' => 'Klarna', 'slug' => 'klarna', 'color' => '#000000', 'show' => ['Klarna']],
        // Crypto
        ['name' => 'Bitcoin', 'slug' => 'bitcoin', 'color' => '#F7931A', 'show' => ['Bitcoin', 'BTC']],
        ['name' => 'Ethereum', 'slug' => 'ethereum', 'color' => '#627EEA', 'show' => ['Ethereum', 'ETH']],
        ['name' => 'BNB', 'slug' => 'bnbchain', 'color' => '#F0B90B', 'show' => ['BNB']],
        ['name' => 'Solana', 'slug' => 'solana', 'color' => '#9945FF', 'show' => ['Solana', 'SOL']],
        ['name' => 'Litecoin', 'slug' => 'litecoin', 'color' => '#345D9D', 'show' => ['Litecoin', 'LTC']],
        ['name' => 'XRP', 'slug' => 'ripple', 'color' => '#23292F', 'show' => ['XRP', 'Ripple']],
        ['name' => 'Dogecoin', 'slug' => 'dogecoin', 'color' => '#C2A633', 'show' => ['Dogecoin', 'DOGE']],
    ],
];
