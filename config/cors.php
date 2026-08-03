<?php

$frontendUrls = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_URL', 'http://localhost:4321,https://dinar-now.com')),
)));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $frontendUrls,

    'allowed_origins_patterns' => [
        '#^https://([a-z0-9-]+\.)?dinar-now\.com$#i',
        '#^https://[a-z0-9-]+\.workers\.dev$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
