<?php

namespace App\Interfaces;

use App\DataTransferObjects\Motor\Response\PremiumResponse;
use App\DataTransferObjects\Motor\Response\ResponseData;
use App\DataTransferObjects\Motor\Response\VIXNCDResponse;

interface InsurerLibraryInterface {
    public function vehicleDetails(object $input) : VIXNCDResponse;
    public function premiumDetails(object $input, $full_quote) : PremiumResponse;
    public function quotation(object $input) : PremiumResponse;
    public function submission(object $input) : ResponseData;
    public function cURL(string $path, string $xml, string $soap_action = null, string $method = 'POST', array $header = []) : ResponseData;
    public function abort(string $message, int $code = 500) : ResponseData;
}