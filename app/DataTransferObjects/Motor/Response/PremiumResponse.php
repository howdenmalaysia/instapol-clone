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
    public $excess_amount = 0;
    
    /** @var float */
    public $gross_premium;

    /** @var float */
    public $loading = 0;
    
    /** @var float */
    public $ncd_amount;
    
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
    

    // Optional Fields
    /** @var string|null */
    public $request_id;

    /** @var float|null */
    public $act_premium;

    /** @var string|null */
    public $detariff;

    /** @var float|null */
    public $detariff_premium = 0;

    /** @var float|null */
    public $discount = 0;

    /** @var float|null */
    public $discount_amount = 0;

    /** @var float|null */
    public $tariff_premium = 0;
}