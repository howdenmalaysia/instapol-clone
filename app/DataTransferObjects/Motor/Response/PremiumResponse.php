<?php

namespace App\DataTransferObjects\Motor\Response;

use Spatie\DataTransferObject\DataTransferObject;

class PremiumResponse extends DataTransferObject
{
    /** @var float */
    public $basic_premium;
    
    /** @var array */
    public $extra_cover;

    /** @var float */
    public $excess_amount = 0.00;
    
    /** @var float */
    public $gross_premium;

    /** @var float */
    public $loading = 0.00;
    
    /** @var float */
    public $ncd_amount;

    /** @var float|int|null */
    public $ncd_percentage;

     /** @var float */
     public $net_premium;
    
    /** @var float */
    public $sst_amount;
    
    /** @var float */
    public $sst_percent;
    
    /** @var float */
    public $stamp_duty;
    
    /** @var float */
    public $total_benefit_amount;
    
    /** @var float */
    public $total_payable;

    /** @var float */
    public $sum_insured;

    /** @var float */
    public $min_sum_insured;

    /** @var float */
    public $max_sum_insured;

    /** @var string */
    public $sum_insured_type;

    /** @var bool */
    public $named_drivers_needed;

    // Optional Fields
    /** @var string|null */
    public $request_id;

    /** @var float|null */
    public $act_premium;

    /** @var string|null */
    public $detariff;

    /** @var float|null */
    public $detariff_premium;

    /** @var float|null */
    public $discount;

    /** @var float|null */
    public $discount_amount;

    /** @var float|null */
    public $tariff_premium;

    /** @var string|null */
    public $quotation_number;

    /** @var object|null */
    public $personal_accident;

    /** @var string|null */
    public $fl_quote_number;

    /** @var string|null */
    public $contract_number;
}