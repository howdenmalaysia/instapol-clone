<?php

namespace App\DataTransferObjects\Motor\Response;

use Spatie\DataTransferObject\DataTransferObject;

class RoadtaxResponse extends DataTransferObject
{
    /** @var float */
    public $roadtax_price;

    /** @var float */
    public $myeg_fee;

    /** @var float */
    public $eservice_fee;

    /** @var float */
    public $delivery_fee;

    /** @var float */
    public $sst;
}