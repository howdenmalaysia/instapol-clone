<?php

namespace App\DataTransferObjects\Motor\Response;

use Spatie\DataTransferObject\DataTransferObject;

class FullQuoteResponse extends DataTransferObject
{
    /** @var string */
    public $company;

    /** @var string */
    public $product_name;

    /** @var int|float */
    public $basic_premium;

    /** @var int|float */
    public $ncd_percentage;

    /** @var int|float */
    public $ncd;

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
    public $excess_amount;

    /** @var int|float */
    public $total_contribution;

    /** @var int|float */
    public $total_payable;

    /** @var int|float|null */
    public $net_premium;

    /** @var \App\DataTransferObjects\Motor\ExtraCover[] */
    public $extra_cover;

    /** @var \App\DataTransferObjects\Motor\Vehicle */
    public $vehicle;
}