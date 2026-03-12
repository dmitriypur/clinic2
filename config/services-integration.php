<?php

return [
    'token' => env('SERVICES_INTEGRATION_TOKEN'),
    'allowed_ips' => array_values(array_filter(array_map(
        static fn(string $ip) => trim($ip),
        explode(',', (string) env('SERVICES_INTEGRATION_ALLOWED_IPS', ''))
    ))),
];
