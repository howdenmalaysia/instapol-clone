<?php

return [
    // General Errors
    'api_error' => ':company Error [:message] (Fault Code: :code)',
    'empty_response' => 'Unable to get :company response (empty response)',
    'xml_error' => 'Failed to load response data',
    'unsupported_id_type' => 'Unsupported ID Type.',
    'variant_not_match' => 'Variant combination does not match.',

    // Underwriting Related
    'gap_in_cover' => 'Policy already lapsed for :days days.',
    'sum_insured_referred' => 'Sum insured amount less than RM 10,000 [RM :sum_insured]',
    'sum_insured_referred_between' => 'Sum insured amount must be between RM :min_sum_insured and RM :max_sum_insured.',
    'insured_age_referred' => 'You must be :min_age years of age or above and :max_age years of age or lower.',
    'vehicle_age_referred' => 'Vehicle must be :age years of age or lower.',
    'earlier_renewal' => 'Unable to renew 2 months earlier from expiry date.',
    'referred_risk' => 'Vehicle falls under refer risks for :company. (:reason)',

    // Logic Related
    'invalid_product' => 'Product not found.',
    'invalid_insurance_status' => 'Invalid Insurance Status. (Insurance Status: :status)',
    'insurance_record_not_match' => 'Insurance record not match.',
    'total_payable_not_match' => 'Premium amount not match.',
];