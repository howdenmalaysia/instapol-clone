<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class VehicleVariantData extends DataTransferObject
{
    /** @var string|null */
    public $insurer;
    
    /** @var string|null */
    public $product_name;
    
    /** @var string|null */
    public $vehicle_number;
    
    /** @var string|null */
    public $make;
    
    /** @var string|null */
    public $model;
    
    /** @var int|float|null */
    public $engine_capacity;
    
    /** @var int|null */
    public $manufacture_year;

    /** @var float|null */
    public float $ncd_percentage;
    
    /** @var string|null */
    public $coverage;
    
    /** @var string|null */
    public $inception_date;
    
    /** @var string|null */
    public $expiry_date;
    
    /** @var float|null */
    public $sum_insured;
    
    /** @var float|null */
    public $min_sum_insured;
    
    /** @var float|null */
    public $max_sum_insured;

    /** @var object */
    public $extra_attribute;

    /**
     * @var \App\DataTransferObjects\Motor\VariantData[]|null
     */
    public $variants;
}