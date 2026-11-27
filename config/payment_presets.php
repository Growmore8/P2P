<?php

// Usual local payment methods per currency, suggested when a currency is added (the admin can change them).
//   pay     = ways clients pay YOU when they buy  (you still enter your own account details)
//   receive = account types clients can add to get paid when they sell (added automatically)
// Types: upi · bank · wallet · link
$bank = fn (string $title = 'Bank transfer') => ['bank', $title];

return [
    'INR' => ['pay' => [['upi', 'UPI (GPay / PhonePe / Paytm)'], $bank('Bank transfer (IMPS / NEFT)')], 'receive' => [['upi', 'UPI'], $bank('Bank transfer (IMPS)')]],
    'LKR' => ['pay' => [$bank('Bank transfer (CEFTS)'), ['wallet', 'eZ Cash'], ['wallet', 'mCash']], 'receive' => [$bank('Bank transfer (CEFTS)'), ['wallet', 'eZ Cash']]],
    'BDT' => ['pay' => [['wallet', 'bKash'], ['wallet', 'Nagad'], $bank()], 'receive' => [['wallet', 'bKash'], ['wallet', 'Nagad'], $bank()]],
    'PKR' => ['pay' => [['wallet', 'JazzCash'], ['wallet', 'Easypaisa'], $bank('Bank transfer (IBFT)')], 'receive' => [['wallet', 'JazzCash'], ['wallet', 'Easypaisa'], $bank('Bank transfer (IBFT)')]],
    'NPR' => ['pay' => [['wallet', 'eSewa'], ['wallet', 'Khalti'], $bank()], 'receive' => [['wallet', 'eSewa'], ['wallet', 'Khalti'], $bank()]],
    'MVR' => ['pay' => [$bank('Bank transfer (BML)')], 'receive' => [$bank('Bank transfer (BML)')]],
    'BTN' => ['pay' => [['wallet', 'mBoB'], $bank()], 'receive' => [['wallet', 'mBoB'], $bank()]],
    'AED' => ['pay' => [$bank('Bank transfer (UAEFTS)')], 'receive' => [$bank('Bank transfer (UAEFTS)')]],
    'SAR' => ['pay' => [$bank('Bank transfer (SARIE)'), ['wallet', 'STC Pay']], 'receive' => [$bank('Bank transfer (SARIE)'), ['wallet', 'STC Pay']]],
    'QAR' => ['pay' => [$bank()], 'receive' => [$bank()]],
    'KWD' => ['pay' => [$bank()], 'receive' => [$bank()]],
    'OMR' => ['pay' => [$bank()], 'receive' => [$bank()]],
    'BHD' => ['pay' => [$bank(), ['wallet', 'BenefitPay']], 'receive' => [$bank(), ['wallet', 'BenefitPay']]],
    'MYR' => ['pay' => [$bank('DuitNow / bank transfer'), ['wallet', "Touch 'n Go eWallet"]], 'receive' => [$bank('DuitNow / bank transfer'), ['wallet', "Touch 'n Go eWallet"]]],
    'SGD' => ['pay' => [$bank('PayNow / bank transfer')], 'receive' => [$bank('PayNow / bank transfer')]],
    'IDR' => ['pay' => [$bank(), ['wallet', 'GoPay'], ['wallet', 'OVO'], ['wallet', 'DANA']], 'receive' => [$bank(), ['wallet', 'GoPay'], ['wallet', 'DANA']]],
    'PHP' => ['pay' => [['wallet', 'GCash'], ['wallet', 'Maya'], $bank('Bank transfer (InstaPay)')], 'receive' => [['wallet', 'GCash'], ['wallet', 'Maya'], $bank('Bank transfer (InstaPay)')]],
    'THB' => ['pay' => [$bank('PromptPay / bank transfer')], 'receive' => [$bank('PromptPay / bank transfer')]],
    'VND' => ['pay' => [$bank(), ['wallet', 'MoMo']], 'receive' => [$bank(), ['wallet', 'MoMo']]],
    'NGN' => ['pay' => [$bank(), ['wallet', 'OPay']], 'receive' => [$bank(), ['wallet', 'OPay']]],
    'KES' => ['pay' => [['wallet', 'M-Pesa'], $bank()], 'receive' => [['wallet', 'M-Pesa'], $bank()]],
    'TZS' => ['pay' => [['wallet', 'M-Pesa'], $bank()], 'receive' => [['wallet', 'M-Pesa'], $bank()]],
    'UGX' => ['pay' => [['wallet', 'MTN Mobile Money'], $bank()], 'receive' => [['wallet', 'MTN Mobile Money'], $bank()]],
    'GHS' => ['pay' => [['wallet', 'MTN Mobile Money'], $bank()], 'receive' => [['wallet', 'MTN Mobile Money'], $bank()]],
    'GBP' => ['pay' => [$bank('Faster Payments'), ['wallet', 'Revolut']], 'receive' => [$bank('Faster Payments'), ['wallet', 'Revolut']]],
    'EUR' => ['pay' => [$bank('SEPA transfer'), ['wallet', 'Revolut']], 'receive' => [$bank('SEPA transfer'), ['wallet', 'Revolut']]],
    'USD' => ['pay' => [$bank('Bank transfer (ACH / wire)'), ['wallet', 'Wise']], 'receive' => [$bank('Bank transfer (ACH / wire)'), ['wallet', 'Wise']]],
    'AUD' => ['pay' => [$bank('PayID / bank transfer')], 'receive' => [$bank('PayID / bank transfer')]],
    'CAD' => ['pay' => [$bank('Interac e-Transfer')], 'receive' => [$bank('Interac e-Transfer')]],
    'NZD' => ['pay' => [$bank()], 'receive' => [$bank()]],

    // Any other currency
    '*' => ['pay' => [$bank()], 'receive' => [$bank()]],
];
