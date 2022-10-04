<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class QuotationData extends DataTransferObject
{
    
    /** @var string */
    public $product_type = '2';
    
    /** @var string */
    public $vehicle_postcode;
    
    /** @var string */
    public $vehicle_no;
    
    /** @var string */
    public $id_type;
    
    /** @var string */
    public $id_no;
    
    /** @var string */
    public $email_address;
    
    /** @var string */
    public $name;
    
    /** @var string */
    public $phone_number;
    
    /** @var string */
    public $postcode;
    
    /** @var string */
    public $checkbox_confirm = 'on';
    
    /** @var string */
    public $terms_of_service_approved = 'on';
    
    /** @var string|null */
    public $h_company_id;
    
    /** @var string|null */
    public $h_product_id;
    
    /** @var App\DataTransferObjects\Motor\VehicleData|null */
    public $h_vehicle;
    
    /** @var App\DataTransferObjects\Motor\VehicleData|null */
    public $h_vehicle_list;
}