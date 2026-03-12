<?php

return [
    'token' => env('SERVICES_INTEGRATION_TOKEN'),
    'default_city_slug' => env('SERVICES_INTEGRATION_DEFAULT_CITY_SLUG'),
    'allowed_ips' => array_values(array_filter(array_map(
        static fn(string $ip) => trim($ip),
        explode(',', (string) env('SERVICES_INTEGRATION_ALLOWED_IPS', ''))
    ))),
];
