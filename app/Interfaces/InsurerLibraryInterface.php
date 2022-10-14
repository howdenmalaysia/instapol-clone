<?php

namespace App\Interfaces;

use App\DataTransferObjects\Motor\Response\PremiumResponse;
use App\DataTransferObjects\Motor\Response\ResponseData;
use App\DataTransferObjects\Motor\Response\VIXNCDResponse;

interface InsurerLibraryInterface {
    public function vehicleDetails(object $input) : object;
    public function premiumDetails(object $input, $full_quote) : object;
    public function quotation(object $input) : object;
    public function submission(object $input) : object;
    public function cURL(string $path, string $xml, string $soap_action = null, string $method = 'POST', array $header = []) : ResponseData;
    public function abort(string $message, int $code = 500) : ResponseData;
}