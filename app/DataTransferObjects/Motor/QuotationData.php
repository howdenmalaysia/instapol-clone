<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class QuotationData extends DataTransferObject
{
    public string $product_type = '2';
    public string $vehicle_postcode;
    public string $vehicle_no;
    public string $id_type;
    public string $id_no;
    public string $email_address;
    public string $name;
    public string $phone_number;
    public string $postcode;
    public string $checkbox_confirm = 'on';
    public string $terms_of_service_approved = 'on';
    public ?string $h_company_id;
    public ?string $h_product_id;
    public VehicleData $h_vehicle;
    public VehicleData $h_vehicle_list;
}