<?php

// Monthly limits per client by verification level, in the base currency (INR): [buy, sell]. 0 = no limit.
// Every other currency is converted automatically at its own USDT rate (see Currency::limitFor).
return [
    'base' => [
        1 => [50000, 50000],       // Level 1 · Identity
        2 => [500000, 500000],     // Level 2 · Video
        3 => [2000000, 2000000],   // Level 3 · EDD
    ],
];
