<?php

namespace App\DataTransferObjects\Motor\Response;

use Spatie\DataTransferObject\DataTransferObject;

class SubmitCoverNoteResponse extends DataTransferObject
{
    /** @var string */
    public $company;

    /** @var string */
    public $product_name;
    
    /** @var string */
    public $policy_number;
}