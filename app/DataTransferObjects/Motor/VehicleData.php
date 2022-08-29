<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class VehicleData extends DataTransferObject
{
    public string $vehRegNo;
    public string $classCode;
	public string $coverage_code;
    public string $vehicle_use_code;
    public string $makeCode;
    public string $make;
    public string $modelCode;
    public string $model;
    public string $yearMake;
    public string $engineNo;
    public string $chassisNo;
    public float $marketValue;
    public float $purchasePrice;
    public string $style;
    public string $nvic;
    public string $variant;
    public string $seatingCapacity;
    public string $engineCapacity;
    public string $ncdEffDate;
    public string $ncdExpDate;
    public string $curNCD;
    public string $nextNCD;
    public string $nextNcdEffDate;
    public string $polExpDate;
    public string $assembly_type_code;
    public float $min_market_value;
    public float $max_market_value;
    public string $ncdcode;
}