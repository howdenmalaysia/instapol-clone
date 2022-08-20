<?php

return [
    'whatsapp' => [
        'url' => env('WHATSAPP_URL'),
        'number' => env('WHATSAPP_NO'),
        'link' => env('WHATSAPP_LINK')
    ],
    'customer_service' => [
        'number' => env('CUSTOMER_SERVICE_NO'),
        'email' => env('CUSTOMER_SERVICE_EMAIL')
    ],
    'redirects' => [
        'motor_extended' => env('MOTOR_EXTENDED_URL'),
        'bicycle' => env('BICYCLE_URL'),
        'travel' => env('TRAVEL_URL'),
        'doc_pro' => env('DOC_PRO_URL'),
    ]
];