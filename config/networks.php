<?php

// USDT networks the admin can add from "+ Add network": name, default fee on buy (USDT) and address format.
return [
    'TRC20' => ['name' => 'Tron (TRC20)', 'fee' => 1.00, 'regex' => '^T[1-9A-HJ-NP-Za-km-z]{33}$', 'hint' => 'starts with T'],
    'BEP20' => ['name' => 'BNB Chain (BEP20)', 'fee' => 0.50, 'regex' => '^0x[a-fA-F0-9]{40}$', 'hint' => 'starts with 0x'],
    'ERC20' => ['name' => 'Ethereum (ERC20)', 'fee' => 5.00, 'regex' => '^0x[a-fA-F0-9]{40}$', 'hint' => 'starts with 0x'],
];
