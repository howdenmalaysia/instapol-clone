<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class APIData extends DataTransferObject
{
    // Get Token
    /** @var string|int|null */
    public $user_id;
    
    /** @var string|null */
    public $agent_no;
    
    /** @var string|int|null */
    public $product_id;
    
    // Get Vehicle
    /** @var int|null */
    public $id_type;
    
    /** @var string|null */
    public $id_number;
    
    /** @var string|null */
    public $vehicle_number;
    
    /** @var string|null */
    public $postcode;
    
    /** @var string|null */
    public $email;
    
    /** @var string|null */
    public $phone_number;
    
    /** @var string|null */
    public $region;
    
    /** @var string|null */
    public $state;

    // Get Quote
    /** @var int|null */
    public $age;
    
    /** @var string|int|null */
    public $insurer_id;

    /** @var string|null */
    public $insurer_name;
    
    /** @var string|null */
    public $gender;
    
    /** @var string|null */
    public $marital_status;
    
    /** @var string|null */
    public $nvic;
    
    /** @var \App\DataTransferObjects\Motor\Vehicle|null */
    public $vehicle;
    
    /** @var array|null */
    public $extra_cover;
    
    /** @var array|null */
    public $additional_driver;
    
    /** @var int|null */
    public $vehicle_body_type;
    
    /** @var string|null */
    public $occupation;

    // Create Quotation
    /** @var string|null */
    public $nationality;

    /** @var string|null */
    public $name;

    /** @var string|null */
    public $date_of_birth;

    /** @var string|null */
    public $phone_code;

    /** @var string|null */
    public $address_one;

    /** @var string|null */
    public $address_two;

    /** @var string|null */
    public $city;

    /** @var object|array|null */
    public $promo;
}