<?php

return [
    'mode' => env('TRIPAY_MODE', 'sandbox'),

    'merchant_code' => env('TRIPAY_MERCHANT_CODE'),
    'api_key' => env('TRIPAY_API_KEY'),
    'private_key' => env('TRIPAY_PRIVATE_KEY'),

    'default_method' => env('TRIPAY_DEFAULT_METHOD', 'QRIS'),

    'base_url' => env('TRIPAY_MODE', 'sandbox') === 'production'
        ? 'https://tripay.co.id/api'
        : 'https://tripay.co.id/api-sandbox',
];
