<?php

namespace App\DataTransferObjects\Motor\Response;

use Spatie\DataTransferObject\DataTransferObject;

class VIXNCDResponse extends DataTransferObject
{
    /** @var string|null */
    public $built_type;
    
    /** @var string */
    public $body_type_code;
    
    /** @var string */
    public $body_type_desription;
    
    /** @var string */
    public $chassis_number;
    
    /** @var string|null */
    public $class_code;
    
    /** @var string */
    public $coverage;
    
    /** @var string|null */
    public $cover_type;
    
    /** @var int */
    public $engine_capacity;
    
    /** @var string */
    public $engine_number;
    
    /** @var string */
    public $expiry_date;
    
    /** @var string */
    public $inception_date;
    
    /** @var string */
    public $liberty_model_code;
    
    /** @var string */
    public $make;
    
    /** @var int */
    public $manufacture_year;
    
    /** @var string|null */
    public $make_code;
    
    /** @var int */
    public $max_sum_insured;
    
    /** @var int */
    public $min_sum_insured;
    
    /** @var string */
    public $model;
    
    /** @var string|null */
    public $model_code;
    
    /** @var float */
    public $ncd_percentage;
    
    /** @var int */
    public $seating_capacity;
    
    /** @var int */
    public $sum_insured;
    
    /** @var string|null */
    public $sum_insured_type;
    
    /** @var string */
    public $variants;
    
    /** @var string */
    public $vehicle_number;
    
    /** @var string */
    public $vehicle_use_code;
    
    /** @var string */
    public $vehicle_type_code;
}

