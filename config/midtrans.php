<?php

declare(strict_types=1);

$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION', false);

return [
    'is_production' => $isProduction,
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'signature_secret_key' => env('MIDTRANS_SIGNATURE_SECRET_KEY'),

    'base_url' => $isProduction
        ? 'https://api.midtrans.com'
        : 'https://api.sandbox.midtrans.com',
];
