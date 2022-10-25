<?php

namespace App\DataTransferObjects\Motor\Response;

use Spatie\DataTransferObject\DataTransferObject;

class QuotationResponse extends DataTransferObject
{
    /** @var string */
    public $insurance_code;

    /** @var array|object */
    public $quotation;
}