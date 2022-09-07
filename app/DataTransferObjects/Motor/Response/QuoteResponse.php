<?php

namespace App\DataTransferObjects\Motor\Response;

use Spatie\DataTransferObject\DataTransferObject;

class QuoteResponse extends DataTransferObject
{
    /** @var string */
    public $make;

    /** @var string */
    public $model;

    /** @var string */
    public $nvic;

    /** @var string */
    public $variant;

    /** @var int */
    public $engine_capacity;

    /** @var int */
    public $manufacture_year;

    /** @var int|float */
    public $ncd_percentage;

    /** @var string */
    public $coverage;

    /** @var string */
    public $inception_date;

    /** @var string */
    public $expiry_date;

    /** @var string */
    public $sum_insured_type;

    /** @var int */
    public $sum_insured;

    /** @var int */
    public $min_sum_insured;

    /** @var int */
    public $max_sum_insured;

    /** @var array|null */
    public $extra_attribute;
}