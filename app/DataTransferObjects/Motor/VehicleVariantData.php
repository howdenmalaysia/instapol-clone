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
    
    /** @var int|null */
    public $engine_capcity;
    
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
    
    /** @var int|null */
    public $sum_insured;
    
    /** @var int|null */
    public $min_sum_insured;
    
    /** @var int|null */
    public $max_sum_insured;

    /**
     * @var \App\DataTransferObjects\Motor\VariantData[]|null
     */
    public $variants;
}

class VariantData extends DataTransferObject
{   
    /** @var string|null */
    public $nvic;
    
    /** @var string|null */
    public $variant;

    /** @var float|null */
    public $sum_insured;
}