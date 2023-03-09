<?php

namespace App\DataTransferObjects\Motor\Response;

use Spatie\DataTransferObject\DataTransferObject;

class QuoteResponse extends DataTransferObject
{
    /** @var string */
    public $company;

    /** @var string */
    public $product_name;
    
    /** @var object|null */
    public $roadtax;

    /** @var array|null */
    public $extra_cover;

    /** @var int|float */
    public $basic_premium;

    /** @var int|float */
    public $ncd_amount;

    /** @var int|float */
    public $total_benefit_amount;

    /** @var int|float */
    public $gross_premium;

    /** @var int|float */
    public $sst_percent;

    /** @var int|float */
    public $sst_amount;

    /** @var int|float */
    public $stamp_duty;

    /** @var int|float */
    public $sum_insured;

    /** @var int|float */
    public $excess_amount;

    /** @var int|float */
    public $loading;

    /** @var int|float */
    public $total_payable;

    /** @var int|float|null */
    public $net_premium;

    /** @var string|null */
    public $quotation_number;
}