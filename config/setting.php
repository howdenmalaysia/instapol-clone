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
    ],
    'id_type' => [
        'nric_no' => 1,
        'company_registration_no' => 2
    ],
    'response_codes' => [
        'unsupported_id_types' => 480,
        'earlier_renewal' => 481,
        'invalid_insurance_status' => 490,
        'insurance_record_mismatch' => 491,
        'total_payable_not_match' => 492
    ],
    'howden' => [
        'short_code' => env('HOWDEN_SHORT_CODE')
    ]
];