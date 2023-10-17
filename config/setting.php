<?php

return [
    'messagebird' => [
        'widget_id' => "10c57c35-cf06-4128-8eae-e9d4fb606d0f"
    ],
    'ga_tracking_id' => "G-00V0BZ105P",
    'whatsapp' => [
        'url' => "https://api.whatsapp.com/send?phone=",
        'number' => "60379890381",
        'link' => "www.instapol.cs/whatsapp"
    ],
    'customer_service' => [
        'number' => "60379890386",
        'email' => "instaPol@howdengroup.com"
    ],
    'redirects' => [
        'motor_extended' => "https://howden-wmotor-dev.instapol.my/landing",
        'bicycle' => "https://howden-bikev2-dev.instapol.my/landing",
        'travel' => "https://howden-travel-dev.instapol.my/",
        'doc_pro' => "https://howden-docpro-dev.instapol.my/docprohome",
        'landlord' => "",
        'miea' => "",
        'sme' => "https://howden-sme.instapol.my/landing",
    ],
    'id_type' => [
        'nric_no' => 1,
        'company_registration_no' => 4
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
        'short_code' => "HIB",
        'affinity_team_email' => empty("dzul.jamal@my.howdengroup.com") ? [] : explode(',', "dzul.jamal@my.howdengroup.com"),
        'email_cc_list' => empty("") ? [] : explode(',', ""),
        'it_dev_mail' => empty("dzul.jamal@my.howdengroup.com") ? [] : explode(',', "dzul.jamal@my.howdengroup.com")
    ],
    'payment' => [
        'gateway' => [
            'url' => "https://pay.e-ghl.com/IPGSG/Payment.aspx",
            'fpx_merchant_id' => "CBI",
            'fpx_merchant_password' => "cbi12345",
            'merchant_id' => "CBH",
            'merchant_name' => "[Dev] Howden",
            'merchant_password' => "cbh12345",
        ]
    ],
    'settlement' => [
        'howden' => [
            'bank_code' => 'CIMB Bank Berhad',
            'bank_account_no' => '8000283395',
            'email_to' => empty('dzul.jamal@my.howdengroup.com') ? [] : explode(',', 'dzul.jamal@my.howdengroup.com'),
            'email_cc' => empty('dzul.jamal@my.howdengroup.com') ? [] : explode(',', 'dzul.jamal@my.howdengroup.com'),
        ],
        'eghl' => [
            'to' => empty('dzul.jamal@my.howdengroup.com') ? [] : explode(',', 'dzul.jamal@my.howdengroup.com'),
            'cc' => empty('dzul.jamal@my.howdengroup.com') ? [] : explode(',', 'dzul.jamal@my.howdengroup.com')
        ]
    ]
];
