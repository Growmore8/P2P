<?php

// USDT networks the admin can add from "+ Add network": name, default fee on buy (USDT) and address format.
return [
    'TRC20' => ['name' => 'Tron (TRC20)', 'fee' => 1.00, 'regex' => '^T[1-9A-HJ-NP-Za-km-z]{33}$', 'hint' => 'starts with T'],
    'BEP20' => ['name' => 'BNB Chain (BEP20)', 'fee' => 0.50, 'regex' => '^0x[a-fA-F0-9]{40}$', 'hint' => 'starts with 0x'],
    'ERC20' => ['name' => 'Ethereum (ERC20)', 'fee' => 5.00, 'regex' => '^0x[a-fA-F0-9]{40}$', 'hint' => 'starts with 0x'],
    'POLYGON' => ['name' => 'Polygon', 'fee' => 0.50, 'regex' => '^0x[a-fA-F0-9]{40}$', 'hint' => 'starts with 0x'],
    'SOL' => ['name' => 'Solana (SPL)', 'fee' => 0.50, 'regex' => '^[1-9A-HJ-NP-Za-km-z]{32,44}$', 'hint' => 'letters and numbers, 32–44 characters'],
    'ARBITRUM' => ['name' => 'Arbitrum One', 'fee' => 0.50, 'regex' => '^0x[a-fA-F0-9]{40}$', 'hint' => 'starts with 0x'],
    'OPTIMISM' => ['name' => 'Optimism', 'fee' => 0.50, 'regex' => '^0x[a-fA-F0-9]{40}$', 'hint' => 'starts with 0x'],
    'AVAXC' => ['name' => 'Avalanche C-Chain', 'fee' => 0.50, 'regex' => '^0x[a-fA-F0-9]{40}$', 'hint' => 'starts with 0x'],
    'TON' => ['name' => 'TON', 'fee' => 0.50, 'regex' => '^(EQ|UQ)[A-Za-z0-9_\-]{46}$', 'hint' => 'starts with EQ or UQ'],
];
