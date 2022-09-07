<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class VehicleData extends DataTransferObject
{
    
    /** @var string */
    public $vehRegNo;
    
    /** @var string */
    public $classCode;
	
    /** @var string */
    public $coverage_code;
    
    /** @var string */
    public $vehicle_use_code;
    
    /** @var string */
    public $makeCode;
    
    /** @var string */
    public $make;
    
    /** @var string */
    public $modelCode;
    
    /** @var string */
    public $model;
    
    /** @var string */
    public $yearMake;
    
    /** @var string */
    public $engineNo;
    
    /** @var string */
    public $chassisNo;
    
    /** @var float */
    public $marketValue;
    
    /** @var float */
    public $purchasePrice;
    
    /** @var string */
    public $style;
    
    /** @var string */
    public $nvic;
    
    /** @var string */
    public $variant;
    
    /** @var string */
    public $seatingCapacity;
    
    /** @var string */
    public $engineCapacity;
    
    /** @var string */
    public string $ncdEffDate;
    
    /** @var string */
    public $ncdExpDate;
    
    /** @var string */
    public $curNCD;
    
    /** @var string */
    public $nextNCD;
    
    /** @var string */
    public $nextNcdEffDate;
    
    /** @var string */
    public $polExpDate;
    
    /** @var string */
    public $assembly_type_code;
    
    /** @var float */
    public $min_market_value;
    
    /** @var float */
    public $max_market_value;
    
    /** @var string */
    public $ncdcode;
}