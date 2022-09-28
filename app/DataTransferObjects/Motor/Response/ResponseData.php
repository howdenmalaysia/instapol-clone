<?php

namespace App\DataTransferObjects\Motor\Response;

use Spatie\DataTransferObject\DataTransferObject;

class ResponseData extends DataTransferObject
{
    
    /** @var bool */
    public $status = true;
    
    /** @var object|string */
    public $response;
    
    /** @var int */
    public $code = 200;

    /** @var array|null */
    public $response_header = null;
}