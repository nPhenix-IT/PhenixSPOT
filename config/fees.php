<?php

return [
    // Total fee applied on online voucher sale (Pay-In fee burden).
    // Example: 5% = 3% Money Fusion + 2% platform margin.
    'sales_commission_percent' => env('SALES_COMMISSION_PERCENT', 5),

    // Fee split metadata for reporting/business visibility.
    'moneyfusion_payin_percent' => env('MONEYFUSION_PAYIN_PERCENT', 3),
    'platform_markup_percent' => env('PLATFORM_MARKUP_PERCENT', 2),
    
    // Total fee applied on withdrawals (Money Fusion fee + platform margin).
    'withdrawal_fee_percent' => env('WITHDRAWAL_FEE_PERCENT', 5),
];
