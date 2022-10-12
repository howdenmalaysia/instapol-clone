<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class VehicleData extends DataTransferObject
{
    
    /** @var string */
    public $vehicle_number;
    
    /** @var string */
    public $class_code;
	
    /** @var string */
    public $coverage_code;
    
    /** @var string */
    public $vehicle_use_code;
    
    /** @var string */
    public $make_code;
    
    /** @var string */
    public $make;
    
    /** @var string */
    public $model_code;
    
    /** @var string */
    public $model;
    
    /** @var string */
    public $manufacture_year;
    
    /** @var string */
    public $engine_number;
    
    /** @var string */
    public $chassis_number;
    
    /** @var float */
    public $market_value = 0.00;
    
    /** @var float */
    public $purchase_price = 0.00;
    
    /** @var string */
    public $style = '';
    
    /** @var string */
    public $nvic;
    
    /** @var string */
    public $variant;
    
    /** @var string */
    public $seating_capacity;
    
    /** @var string */
    public $engine_capacity;
    
    /** @var string */
    public string $ncd_effective_date;
    
    /** @var string */
    public $ncd_expiry_date;
    
    /** @var string */
    public $current_ncd;
    
    /** @var string */
    public $next_ncd;
    
    /** @var string */
    public $next_ncd_effective_date;
    
    /** @var string */
    public $policy_expiry_date;
    
    /** @var string */
    public $assembly_type_code = '';
    
    /** @var float */
    public $min_market_value = 0.00;
    
    /** @var float */
    public $max_market_value = 0.00;
    
    /** @var string */
    public $ncd_code = '';
}