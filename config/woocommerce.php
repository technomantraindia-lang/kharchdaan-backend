<?php

return [
    'url' => env('WOOCOMMERCE_URL', ''),
    'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY', ''),
    'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET', ''),
    'api_version' => env('WOOCOMMERCE_API_VERSION', 'wc/v3'),
    'webhook_secret' => env('WOOCOMMERCE_WEBHOOK_SECRET', ''),
    'timeout' => (int) env('WOOCOMMERCE_TIMEOUT', 30),
];
