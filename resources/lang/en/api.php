<?php

return [
    // General Errors
    'api_error' => ':company Error [:message] (Fault Code: :code)',
    'empty_response' => 'Unable to get :company response (Empty Response)',
    'xml_error' => 'Failed to load response data',
    'unsupported_id_type' => 'Unsupported ID Type.',
    'variant_not_match' => 'Variant combination does not match.',
    'file_not_found' => 'Mapping file not found. (Mapping File Type: :type)',
    'empty_nvic' => 'NVIC code is empty. (Insurer ID: :insurer_id)',

    // Underwriting Related
    'gap_in_cover' => 'It seems like your car insurance has expired more than :days days. Please WhatsApp us for help.',
    'sum_insured_referred' => 'It seems like your market value of your car is less than RM 10,000.00. Please WhatsApp us for help. [RM :sum_insured]',
    'sum_insured_referred_between' => 'Sum insured amount must be between RM :min_sum_insured and RM :max_sum_insured.',
    'insured_age_referred' => 'You must be :min_age years of age or above and :max_age years of age or lower.',
    'vehicle_age_referred' => 'Vehicle must be :age years of age or lower.',
    'referred_risk' => 'Vehicle falls under refer risks for :company. (:reason)',
    'earlier_renewal' => 'Unfortunately, you may only renew your policy within 2 months before your policy\'s expiry date. Any questions? Feel free to reach out to our Customer Service team.',
    'undergoing_renewal' => 'It seems like your car is undergoing renewal process by another provider. Please WhatsApp us for help.',
    'invalid_vehicle_number' => 'Your vehicle registration number is either invalid or does not match the registered owner of the vehicle. Please WhatsApp us for help.',
    'invalid_id_number' => 'Your ID number is either invalid OR your car number is not match the registered owner of the vehicle. Please WhatsApp us for help.',
    'unable_to_get_ncd' => 'Unfortunately, we\'re unable to get the NCD for your vehicle. Please WhatsApp us for help.',

    // Logic Related
    'invalid_product' => 'Product not found.',
    'invalid_insurance_status' => 'Invalid Insurance Status. (Insurance Status: :status)',
    'insurance_record_not_match' => 'Insurance record not match.',
    'total_payable_not_match' => 'Premium amount not match.',
    'promo_code_not_found' => 'Promo code that you entered doesn\'t exist in our record',
    'promo_hasnt_start' => 'Promotion is only valid from :start onwards.',
    'promo_expired' => 'Promo code you entered has expired.',
    'promo_code_ran_out' => 'Promo code has been fully redeemed.',
    'promo_domain_not_allowed' => 'Your email address is not entitled to use this promo code. Please WhatsApp us if you think this is wrong.',
    'promo_zero_discount' => 'Something Went Wrong. Please WhatsApp us for help. (Code: ZERO_DISCOUNT_AMOUNT)',
    'promo_discount_target_not_found' => 'Invalid discount target for :code.',
    'promo_min_spend_not_achieved' => 'Spend :amount more to use this promo code',
];