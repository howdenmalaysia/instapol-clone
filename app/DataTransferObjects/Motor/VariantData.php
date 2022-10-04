<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class VariantData extends DataTransferObject
{   
    /** @var string|null */
    public $nvic;
    
    /** @var string|null */
    public $variant;

    /** @var float|null */
    public $sum_insured;
}