<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class Vehicle extends DataTransferObject
{
    /** @var string */
    public $make;

    /** @var string|null */
    public $model;

    /** @var string */
    public $nvic;

    /** @var string|null */
    public $variant;

    /** @var int|float */
    public $engine_capacity;

    /** @var int */
    public $manufacture_year;

    /** @var int|float */
    public $ncd_percentage;

    /** @var string */
    public $coverage;

    /** @var string|null */
    public $coverage_type;

    /** @var string */
    public $inception_date;

    /** @var string */
    public $expiry_date;

    /** @var string */
    public $sum_insured_type;

    /** @var int|float */
    public $sum_insured;

    /** @var int|float */
    public $min_sum_insured;

    /** @var int|float */
    public $max_sum_insured;

    /** @var object|null */
    public $extra_attribute;
}