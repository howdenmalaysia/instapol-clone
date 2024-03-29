<?php

return [
    'messagebird' => [
        'widget_id' => env('MB_WIDGET_ID')
    ],
    'ga_tracking_id' => env('GA_TRACKING_ID'),
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
        'landlord' => env('LANDLORD_URL'),
        'miea' => env('MIEA_URL'),
        'sme' => env('SME_URL'),
        'hho' => env('HHO_URL'),
        'pickles' => env('PICKLES_URL'),
        'critical' => env('CRITICAL_URL'),
    ],
    'id_type' => [
        'nric_no' => 1,
        'company_registration_no' => 4
    ],
    'service_tax' => [
        'sst' => env('MIX_SERVICE_CHARGE_R', 0.06),
       	'word' => env('SERVICE_TAX', 6),
    ],
    'response_codes' => [
        // Underwriting Related
        'earlier_renewal' => 460,
        'sum_insured_referred' => 461,
        'gap_in_cover' => 462,
        'undergoing_renewal' => 463,
        'invalid_id_number' => 464,
        'invalid_vehicle_number' => 465,
        'data_not_found' => 466,
        'unable_to_get_ncd' => 467,
        'blacklisted_vehicle' => 468,

        // Logic Related
        'unsupported_id_types' => 480,
        'invalid_insurance_status' => 481,
        'insurance_record_mismatch' => 482,
        'total_payable_not_match' => 483,
        'general_error' => 490
    ],
    'howden' => [
        'short_code' => env('HOWDEN_SHORT_CODE'),
        'affinity_team_email' => empty(env('AFFINITY_TEAM_EMAIL')) ? [] : explode(',', env('AFFINITY_TEAM_EMAIL')),
        'email_cc_list' => empty(env('EMAIL_CC_LIST')) ? [] : explode(',', env('EMAIL_CC_LIST')),
        'it_dev_mail' => empty(env('HOWDEN_IT_DEV_MAIL')) ? [] : explode(',', env('HOWDEN_IT_DEV_MAIL')),
        'insta_admin' => empty(env('INSTAADMIN_EMAIL')) ? [] : explode(',', env('INSTAADMIN_EMAIL')),
        'contact_list' => [
            'jeffery_chan' => 'jeffreycw.chan@my.howdengroup.com',
            'phoebie_wong' => 'phoebie.wong@my.howdengroup.com',
            'cheng_lai_fah' => 'laifah.cheng@my.howdengroup.com'
        ]
    ],
    'payment' => [
        'gateway' => [
            'url' => env('EGHL_PAYMENT_URL'),
            'fpx_merchant_id' => env('EGHL_FPX_MERCHANT_ID'),
            'fpx_merchant_password' => env('EGHL_FPX_MERCHANT_PASSWORD'),
            'merchant_id' => env('EGHL_MERCHANT_ID'),
            'merchant_name' => env('EGHL_MERCHANT_NAME'),
            'merchant_password' => env('EGHL_MERCHANT_PASSWORD'),
        ]
    ],
    'settlement' => [
        'howden' => [
            'bank_code' => 'CIMB Bank Berhad',
            'bank_account_no' => '8000283395',
            'email_to' => empty(env('HOWDEN_INTERNAL')) ? [] : explode(',', env('HOWDEN_INTERNAL')),
            'email_cc' => empty(env('HOWDEN_INTERNAL_CC')) ? [] : explode(',', env('HOWDEN_INTERNAL_CC')),
        ],
        'eghl' => [
            'to' => empty(env('EGHL_SETTLEMENT')) ? [] : explode(',', env('EGHL_SETTLEMENT')),
            'cc' => empty(env('EGHL_SETTLEMENT_CC')) ? [] : explode(',', env('EGHL_SETTLEMENT_CC'))
        ]
    ]
];
