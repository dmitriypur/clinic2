<?php

return [
    'clinic_uuid' => env('CLINIC_UUID'),
    'base_url' => env('UNF_BASE_URL'),
    'urls' => [
        'services' => 'events?action=services',
        'appointment' => 'events?action=newrecord',
        'callback' => 'events?action=callrequest',
        'profile' => 'events?action=authorization',
        'source' => 'events?action=source',
        'form' => 'events?action=form',
        'schedule' => 'events?action=raspisanie',
    ],
    'sms_aero' => [
        'user_login' => env('SMS_AERO_USER_LOGIN', ''),
        'api_key' => env('SMS_AERO_API_KEY', ''),
    ],
    'lo_token' => env('LO_TOKEN', ''),
];
