<?php

return [
    'clinic_uuid' => env('CLINIC_UUID'),
    'base_url' => env('UNF_BASE_URL'),
    'urls' => [
        'services' => 'events?action=services',
        'appointment' => 'events?action=newrecord',
        'callback' => 'events?action=callback',
        'profile' => 'events?action=authorization',
        'source' => 'events?action=source',
        'form' => 'events?action=spravka',
        'schedule' => 'events?action=raspisanie',
        'callback-old' => 'events?action=callrequest',
    ],
    'sms_aero' => [
        'user_login' => env('SMS_AERO_USER_LOGIN', ''),
        'api_key' => env('SMS_AERO_API_KEY', ''),
    ],
    'lo_token' => env('LO_TOKEN', ''),
    'booking_api_base_url' => env('BOOKING_API_BASE_URL', 'https://adminzrenie.ru/api/v1'),
    'booking_allowed_clinic_ids' => array_values(array_filter(array_map(
        static fn(string $id): int => (int) trim($id),
        explode(',', (string) env('BOOKING_ALLOWED_CLINIC_IDS', '1,2'))
    ))),
];
