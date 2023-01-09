<?php

namespace App\Helpers\Insurer;

use App\DataTransferObjects\Motor\Response\ResponseData;
use App\Helpers\HttpClient;
use App\Interfaces\InsurerLibraryInterface;
use App\Models\APILogs;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Allianz implements InsurerLibraryInterface
{
    private string $host;
    private string $url_token;
    private string $url;
    private string $token;
    private string $agent_code;
    private string $company_id;
    private string $company_name;

	private string $username;
	private string $password;

	public function __construct(int $insurer_id, string $insurer_name)
    {
        $this->company_id = $insurer_id;
        $this->company_name = $insurer_name;

		$this->host = config('insurer.config.allianz_config.host');
		$this->url_token = config('insurer.config.allianz_config.url_token');
		$this->url = config('insurer.config.allianz_config.url');
		$this->username = config('insurer.config.allianz_config.username');
		$this->password = config('insurer.config.allianz_config.password');
	}

	public function get_token(){
		if(empty($this->token)){
			$token = $this->cURL('token');
            
			if($token->status && isset($token->response->access_token)){
				$this->token = $token->response->access_token;

				return $token->response->access_token;
            }
			else{
                return $this->abort($token->response);
            }
        }
		else{
			return $this->token;
        }
	}

    public function vehicleDetails(object $input) : object
    {
		$source_system = $input->source_system;
		$vehicle_license_id = $input->vehicle_license_id;
		$identity_type = $input->identity_type;
		$identity_number = $input->identity_number;
		$check_ubb_ind = intval($input->check_ubb_ind);
		$postal_code = $input->postal_code;
        $text = '{
            "sourceSystem": "'.$source_system.'",
            "vehicleLicenseId": "'.$vehicle_license_id.'",
            "identityType": "'.$identity_type.'",
            "identityNumber": "'.$identity_number.'",
            "checkUbbInd": '.$check_ubb_ind.',
            "postalCode": "'.$postal_code.'"
        }';
        $result = $this->cURL("getData", "/vehicleDetails", $text);

        if(!$result->status) {
            return $this->abort($result->response);
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
    }

    public function checkUBB(object $input) : object
    {  
        $postcode_details = $this->postalCode($input->postcode);
        $get_vehicle_details = (object)[
            'source_system' => "PARTNER_ID",
            'vehicle_license_id' => $input->vehicle_number,
            'identity_type' => "NRIC",
            'identity_number' => $input->id_number,
            'check_ubb_ind' => 1,
            'postal_code' => $postcode_details->Postcode,
        ];
        $vix = $this->vehicleDetails($get_vehicle_details)->response;
        $get_avvariant = (object)[
            'region' => $postcode_details->Region,
            'makeCode' => $vix->vehicleMake,
            'modelCode' => $vix->vehicleModel,
            'makeYear' => $vix->yearOfManufacture,
        ];
        $avvariant = $this->avVariant($get_avvariant);
        $get_quotation = (object)[
            'input'=>$input,
            'vix'=>$vix,
            'avvariant'=>$avvariant,
        ];
        // $quotation = $this->getQuotation($get_quotation)->response;
        dd($avvariant);
        $quotation = $this->quotation($get_quotation);
        dd($quotation);
        $text = '{
            "ReferenceNo": "CNAZ00000003637",
            "ProductCat": "MT",
            "SourceSystem": "PARTNER_ID",
            "ClaimsExp": "0",
            "ReconInd": "N",
            "ExcessWaiveInd": false,
            "CheckUbbInd": 2,
            "Policy": {
              "PolicyEffectiveDate": "2018-11-12",
              "PolicyExpiryDate": "2019-11-11",
              "Client": {
                "IdentificationNumber": "810323145146",
                "IdType": "NRIC",
                "Age": "41"
              },
              "RiskList": [
                {
                  "RiskId": "1",
                  "InsuredPerson": {
                    "IdentificationNumber": "841103011116",
                    "IdType": "NRIC"
                  },
                  "Vehicle": {
                    "AvCode": "HONDA",
                    "Capacity": "1497",
                    "MakeCode": "11",
                    "Model": "CITY",
                    "PiamModel": "28",
                    "Seat": 5,
                    "VehicleNo": "vj8152",
                    "YearOfManufacture": "2015",
                    "NamedDriverList": [
                      {
                        "Age": "34",
                        "IdentificationNumber": "841103011116"
                      }
                    ],
                    "HighPerformanceInd": false,
                    "HrtvInd": false
                  },
                  "CoverList": [
                    {
                      "CoverPremium": {
                        "SumInsured": "61000.00"
                      }
                    }
                  ]
                }
              ]
            }
          }
        ';
        $json = json_encode($text);
		$data = array(
			'requestData' => $json
		);
		$ubb = $this->cURL("getData", "/checkUBB", $data);
        $response = (object)[
            'ReferRiskList' => '',
            'CoverId' => '',
            'ReferCode' => '',
            'ReferLevel' => '',
            'RiskId' => '',
            'RoutingCode' => '',
        ];
    }

    public function allianzMake(object $input) : object
    {
        $make_code = "";
        if(isset($input->makeCode) && $input->makeCode != ''){
            $make_code = "?makeCode=".$input->makeCode;
        }
        $function = 'allianzMake';

		$result = $this->cURL("GET", "/lov/allianzMake".$make_code);
		
        if(!$result->status) {
            return $this->abort($result->response);
        }
        if($result->response == ''){
            return $this->abort($function.' is getting empty result!');
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
    }

    
    public function allianzModel(object $input) : object
    {
        $make_code = "?makeCode=".$input->makeCode;
        $model_code = "";
        if(isset($input->modelCode) && $input->modelCode != ''){
            $model_code = "&modelCode=".$input->modelCode;
        }
        $function = 'allianzModel';
		$result = $this->cURL("GET", "/lov/allianzModel".$make_code.$model_code);

        if(!$result->status) {
            return $this->abort($result->response);
        }
        if($result->response == ''){
            return $this->abort($function.' is getting empty result!');
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
    }
    
    public function allianzVariant(object $input) : object
    {
        $make_code = "?makeCode=".$input->makeCode;
        $model_code = "";
        if(isset($input->modelCode) && $input->modelCode != ''){
            $model_code = "&modelCode=".$input->modelCode;
        }
        $function = 'allianzVariant';
		$result = $this->cURL("GET", "/lov/allianzVariant".$make_code.$model_code);

        if(!$result->status) {
            return $this->abort($result->response);
        }
        if($result->response == ''){
            return $this->abort($function.' is getting empty result!');
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
    }
    
    public function avMake(object $input) : object
    {
        $region = "?region=".$input->region;
        $make_code = "";//HONDA
        if(isset($input->makeCode) && $input->makeCode != ''){
            $make_code = "&makeCode=".$input->makeCode;
        }
        $function = 'avMake';
		$result = $this->cURL("GET", "/lov/avMake".$region.$make_code);

        if(!$result->status) {
            return $this->abort($result->response);
        }
        if($result->response == ''){
            return $this->abort($function.' is getting empty result!');
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
    }
    
    public function avModel(object $input) : object
    {
        $region = "?region=".$input->region;
        $make_code = "&makeCode=".$input->makeCode;
        $model_code = "";
        if(isset($input->modelCode) && $input->modelCode != ''){
            $model_code = "&modelCode=".$input->modelCode;
        }
        $function = 'avModel';//CITY
		$result = $this->cURL("GET", "/lov/avModel".$region.$make_code.$model_code);
        
        if(!$result->status) {
            return $this->abort($result->response);
        }
        if($result->response == ''){
            return $this->abort($function.' is getting empty result!');
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
    }
    
    public function avVariant(object $input) : object
    {
        $region = "?region=".$input->region;
        $make_code = "&makeCode=".$input->makeCode;
        $model_code = "";
        if(isset($input->modelCode) && $input->modelCode != ''){
            $model_code = "&modelCode=".$input->modelCode;
        }
        $make_year = "";
        if(isset($input->makeYear) && $input->makeYear != ''){
            $make_year = "&makeYear=".$input->makeYear;
        }
        $function = 'avVariant';
		$result = $this->cURL("GET", "/lov/avVariant".$region.$make_code.$model_code.$make_year);
		
        if(!$result->status) {
            return $this->abort($result->response);
        }
        if($result->response == ''){
            return $this->abort($function.' is getting empty result!');
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
    }

    public function premiumDetails(object $input, $full_quote = false) : object
    {
        $vehicle = $input->vehicle ?? null;
        $ncd_amount = $basic_premium = $total_benefit_amount = $gross_premium = $sst_percent = $sst_amount = $stamp_duty = $excess_amount = $total_payable = 0;
        $pa = null;

        if ($full_quote) {
            $postcode_details = $this->postalCode($input->postcode);
            $get_vehicle_details = (object)[
                'vehicle_number' => $input->vehicle_number,
                'id_type' => $this->id_type($input->id_type),
                'id_number' => $input->id_number,
                'postcode' => $postcode_details->Postcode,
            ];
            $vehicle_vix = $this->vehicleDetails($get_vehicle_details);
            if (!$vehicle_vix->status) {
                return $this->abort($vehicle_vix->response, $vehicle_vix->code);
            }
            $get_avvariant = (object)[
                'region' => $postcode_details->Region,
                'makeCode' => $vehicle_vix->response->make,
                'modelCode' => $vehicle_vix->response->model,
                'makeYear' => $vehicle_vix->response->manufacture_year,
            ];
            $avvariant = $this->avVariant($get_avvariant)->response;
            // Get Selected Variant
            $selected_variant = null;
            if ($input->nvic == '-') {
                if (count($vehicle_vix->response->variants) == 1) {
                    $selected_variant = $vehicle_vix->response->variants[0];
                }
            } else {
                foreach ($vehicle_vix->response->variants as $_variant) {
                    if ($input->nvic == $_variant->nvic) {
                        $selected_variant = $_variant;
                        break;
                    }
                }
            }

            // if (empty($selected_variant)) {
            //     return $this->abort(trans('api.variant_not_match'));
            // }

            // set vehicle
            $vehicle = new Vehicle([
                'make' => $vehicle_vix->response->make,
                'model' => $vehicle_vix->response->model,
                'nvic' => $selected_variant->nvic ?? $input->nvic,
                'variant' => $selected_variant->variant ?? $input->vehicle->variant,
                'engine_capacity' => $vehicle_vix->response->engine_capacity,
                'manufacture_year' => $vehicle_vix->response->manufacture_year,
                'ncd_percentage' => $vehicle_vix->response->ncd_percentage,
                'coverage' => $vehicle_vix->response->coverage,
                'inception_date' => $vehicle_vix->response->inception_date,
                'expiry_date' => $vehicle_vix->response->expiry_date,
                'sum_insured_type' => $vehicle_vix->response->sum_insured_type,
                'sum_insured' => $vehicle_vix->response->sum_insured,
                'min_sum_insured' => $vehicle_vix->response->min_sum_insured,
                'max_sum_insured' => $vehicle_vix->response->max_sum_insured,
                'extra_attribute' => (object) [
                    'chassis_number' => $vehicle_vix->response->chassis_number,
                    'cover_type' => $vehicle_vix->response->cover_type,
                    'engine_number' => $vehicle_vix->response->engine_number,
                    'seating_capacity' => $vehicle_vix->response->seating_capacity,
                ],
            ]);
            // get premium
            $get_quotation = (object)[
                'input'=>$input,
                'vix'=>$vehicle_vix,
                'avvariant'=>$avvariant,
            ];
            $motor_premium = $this->getQuotation($get_quotation);

            if (!$motor_premium->status) {
                return $this->abort($motor_premium->response);
            }

            $basic_premium = formatNumber($motor_premium->response->premium->basicPremium);
            $excess_amount = formatNumber($motor_premium->response->premium->excessAmount);
            $ncd_percentage = $vehicle->ncd_percentage;
            $ncd_amount = formatNumber($motor_premium->response->premium->ncdAmt);
            //$total_benefit_amount = formatNumber($motor_premium->response->EXTRACOVERAGE_AMOUNT);
            $gross_premium = formatNumber($motor_premium->response->premium->grossPremium);
            $sst_percent = formatNumber($motor_premium->response->premium->serviceTaxPercentage);
            $sst_amount = formatNumber($motor_premium->response->premium->serviceTaxAmount);
            $stamp_duty = formatNumber($motor_premium->response->premium->stampDuty);
            $total_payable = formatNumber($motor_premium->response->premium->premiumDueRoundedAfterPTV);//?????
            $net_premium = formatNumber($motor_premium->response->premium->premiumDueRoundedAfterPTV - $motor_premium->response->premium->commissionAmount);

            // Remove Extra Cover which is not entitled
            $available_benefits = self::EXTRA_COVERAGE_LIST;
            
            // Generate Extra Cover List
            foreach($available_benefits as $extra_cover_code) {
                $_sum_insured_amount = $_cart_amount = $_cart_day = 0;

                $item = new ExtraCover([
                    'selected' => false,
                    'readonly' => false,
                    'extra_cover_code' => $extra_cover_code,
                    'extra_cover_description' => $this->getExtraCoverDescription($extra_cover_code),
                    'sum_insured' => 0,
                    'premium' => 0,
                ]);

                switch($extra_cover_code) {
                    case '89A': { // Windscreen Damage
                        // Generate Options From 500 To 10,000
                        $option_list = new OptionList([
                            'name' => 'sum_insured',
                            'description' => 'Sum Insured Amount',
                            'values' => generateExtraCoverSumInsured(500, 10000, 1000),
                            'any_value' => true,
                            'increment' => 100
                        ]);

                        $item->option_list = $option_list;

                        // Default to RM 1,000
                        $_sum_insured_amount = $option_list->values[1];

                        break;
                    }
                    case '112': { // Compensation For Assessed Repair Time (CART)
                        // Get CART Days & Its Amount
                        $cart_list = [];

                        foreach (self::CART_DAY_LIST as $_cart_day) {
                            $cart_amount_list = [];

                            foreach (self::CART_AMOUNT_LIST as $_cart_amount) {
                                array_push($cart_amount_list, $_cart_amount);
                            }

                            array_push($cart_list, new CartList([
                                'cart_day' => $_cart_day,
                                'cart_amount_list' => $cart_amount_list
                            ]));
                        }

                        $item->cart_list = $cart_list;

                        // Get The Lowest CART Day & Amount / Day To Get Premium
                        $_cart_day = $cart_list[0]->cart_day;
                        $_cart_amount = $cart_list[0]->cart_amount_list[0];

                        break;
                    }
                    case '97A': { // Gas Conversion Kit And Tank
                        // Generate Options From 1,000 To 10,000
                        $option_list = new OptionList([
                            'name' => 'sum_insured',
                            'description' => 'Sum Insured Amount',
                            'values' => generateExtraCoverSumInsured(1000, 10000, 1000),
                            'any_value' => true,
                            'increment' => 100
                        ]);

                        $item->option_list = $option_list;

                        // Default to RM 1,000
                        $_sum_insured_amount = $option_list->values[0];

                        break;
                    }
                }

                if (!empty($_sum_insured_amount)) {
                    $item->sum_insured = $_sum_insured_amount;
                } elseif (!empty($_cart_day) && !empty($_cart_amount)) {
                    $item->cart_day = $_cart_day;
                    $item->cart_amount = $_cart_amount;
                }

                // Include into $input->extra_cover to get the premium
                array_push($input->extra_cover, $item);
            }
        }

        $response = new PremiumResponse([
            'basic_premium' => formatNumber($motor_premium->response->premium->basicPremium),
            'ncd_percentage' => $motor_premium->response->premium->ncdPct,
            'ncd_amount' => formatNumber($motor_premium->response->premium->ncdAmt),
            'total_benefit_amount' => 0.00, //formatNumber($motor_premium->response->premium->EXTRACOVERAGE_AMOUNT),
            'gross_premium' => formatNumber($motor_premium->response->premium->grossPremium),
            'sst_percent' => formatNumber($motor_premium->response->premium->serviceTaxPercentage),
            'sst_amount' => formatNumber($motor_premium->response->premium->serviceTaxAmount),
            'stamp_duty' => formatNumber($motor_premium->response->premium->stampDuty),
            'excess_amount' => formatNumber($motor_premium->response->premium->excessAmount),
            'total_payable' => formatNumber($motor_premium->response->premium->premiumDueRoundedAfterPTV),
            'net_premium' => formatNumber($motor_premium->response->premium->premiumDueRoundedAfterPTV - $motor_premium->response->premium->commissionAmount),
            'extra_cover' => $input->extra_cover,
            'personal_accident' => $pa,
            // 'quotation_number' => $motor_premium->response->premium->QUOTATION_NO,
            'sum_insured' => formatNumber($vehicle_vix->response->sum_insured ?? 0),
            'sum_insured_type' => $vehicle_vix->response->sum_insured_type,
            'min_sum_insured' => formatNumber($vehicle_vix->response->min_sum_insured),
            'max_sum_insured' => formatNumber($vehicle_vix->response->max_sum_insured),
            'named_drivers_needed' => false
        ]);

        if ($full_quote) {
            // Revert to premium without extra covers
            $response->basic_premium = $basic_premium;
            $response->ncd_percentage = $ncd_percentage;
            $response->ncd_amount = $ncd_amount;
            // $response->total_benefit_amount = $total_benefit_amount;
            $response->total_benefit_amount = 0.00;
            $response->gross_premium = $gross_premium;
            $response->sst_percent = $sst_percent;
            $response->sst_amount = $sst_amount;
            $response->stamp_duty = $stamp_duty;
            $response->excess_amount = $excess_amount;
            $response->total_contribution = $total_payable;
            $response->total_payable = $total_payable;
            $response->net_premium = $net_premium;

            $response->vehicle = $vehicle;
        }
        return (object) ['status' => true, 'response' => $response];
    }

    private function getExtraCoverDescription(string $extra_cover_code) : string
    {
        $extra_cover_name = '';

        switch($extra_cover_code) {
            case '01': { 
                $extra_cover_name = 'All Drivers';
                break;
            }
            case '02': { 
                $extra_cover_name = 'Legal Liability to Passengers';
                break;
            }
            case '03': { 
                $extra_cover_name = 'All Riders';
                break;
            }
            case '06': { 
                $extra_cover_name = 'Tuition';
                break;
            }
            case '07': { 
                $extra_cover_name = 'Additional Drivers';
                break;
            }
            case '101': { 
                $extra_cover_name = 'Extension of Kindom of Thailand';
                break;
            }
            case '103': { 
                $extra_cover_name = 'Malicious Damage';
                break;
            }
            case '108': { 
                $extra_cover_name = 'Passenger Liability Cover';
                break;
            }
            case '109': { 
                $extra_cover_name = 'Ferry Transit To and/or Sabah And The Federal';
                break;
            }
            case '111': { 
                $extra_cover_name = 'Current Year NCD Relief (Comp Private Car)';
                break;
            }
            case '112': { 
                $extra_cover_name = 'Cart';
                break;
            }
            case '19': { 
                $extra_cover_name = 'Passenger Risk';
                break;
            }
            case '22': { 
                $extra_cover_name = 'Caravan / Luggage / Trailers (Private Car Only)';
                break;
            }
            case '25': { 
                $extra_cover_name = 'Strike Riot & Civil Commotion';
                break;
            }
            case '57': { 
                $extra_cover_name = 'Inclusion Of Special Perils';
                break;
            }
            case '72': { 
                $extra_cover_name = 'Legal Liability Of Passengers For Negligent Acts';
                break;
            }
            case '89': { 
                $extra_cover_name = 'Breakage Of Glass In WindScreen, Window Or Sunroof';
                break;
            }
            case '89A': { 
                $extra_cover_name = 'Windscreen Damage';
                break;
            }
            case '97': { 
                $extra_cover_name = 'Vehicle Accessories Endorsement';
                break;
            }
            case '97A': { 
                $extra_cover_name = 'Gas Conversion Kit And Tank';
                break;
            }
            case '200': { 
                $extra_cover_name = 'PA Basic';
                break;
            }
            case '201': { 
                $extra_cover_name = 'Temporary Courtesy Car';
                break;
            }
            case '202': { 
                $extra_cover_name = 'Towing And Cleaning Due To Water Damage';
                break;
            }
            case '203': { 
                $extra_cover_name = 'Key Replacement';
                break;
            }
        }

        return $extra_cover_name;
    }

    public function submission(object $input) : object
    {

    }

    public function abort(string $message, int $code = 490) : ResponseData
    {
        return new ResponseData([
            'status' => false,
            'response' => $message,
            'code' => $code
        ]);
    }

    public function quotation(object $qParams) : object
    {
        $data = (object) [
            'vehicle_number' => $input->vehicle_number,
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'gender' => $input->gender,
            'marital_status' => $input->marital_status,
            'region' => $input->region,
            'vehicle' => $input->vehicle,
            'extra_cover' => $input->extra_cover,
            'email' => $input->email,
            'phone_number' => $input->phone_number,
            'nvic' => $input->vehicle->nvic,
            'unit_no' => $input->unit_no ?? '',
            'building_name' => $input->building_name ?? '',
            'address_one' => $input->address_one,
            'address_two' => $input->address_two ?? '',
            'city' => $input->city,
            'postcode' => $input->postcode,
            'state' => $input->state,
            'occupation' => $input->occupation,
        ];

        $result = $this->premiumDetails($data);

        if (!$result->status) {
            return $this->abort($result->response);
        }

        $result->response->quotation_number = $result->response->quotation_number;

        return (object) [
            'status' => true,
            'response' => $result->response
        ];
    }

    public function getQuotation(object $qParams) : object
    {
        $dobs = str_split($qParams->input->id_number, 2);
        $id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
        $year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}
		$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);
        $text = '{
            "partnerId": "PARTNERID",
            "contractNumber": "'.$qParams->vix->contractNumber.'",
            "effectiveDate": "'.Carbon::now()->format('Y-m-d').'",
            "expirationDate": "'.Carbon::now()->addYear()->subDay()->format('Y-m-d').'",
            "person": {
                "identityType": "NRIC",
                "identityNumber": "'.$qParams->input->id_number.'",
                "gender": "'.$qParams->input->gender.'",
                "birthDate": "'.$dob.'",
                "maritalStatus": "'.$qParams->input->marital_status.'",
                "postalCode": "'.$qParams->input->postcode.'",
                "noOfClaims": "0"
            },
            "calculateDiscount": {
                "discountPercentage": ""
            },
            "vehicle": {
                "vehicleLicenseId": "'.$qParams->vix->vehicleLicenseId.'",
                "vehicleMake": "'.$qParams->vix->makeCode.'",
                "vehicleModel": "'.$qParams->vix->modelCode.'",
                "vehicleEngineCC": '.$qParams->vix->vehicleEngineCC.',
                "yearOfManufacture": "'.$qParams->vix->yearOfManufacture.'",
                "occupantsNumber": '.$qParams->vix->seatingCapacity.',
                "ncdPercentage": '.$qParams->vix->ncdPercentage.',
                "sumInsured": "'.$qParams->avvariant->SumInsured.'",
                "avCode": "'.$qParams->avvariant->AvCode.'",
                "mvInd": "Y"
            }
        }';
		$ubb = $this->cURL("getData", "/quote", $text);
        
        if(!$ubb->status) {
            return $this->abort($result->response);
        }
        return $ubb;
	}

    private function postalCode(string $PostCode)
    {
        $text = '{
            "PostCode": "'.$PostCode.'"
          }';
        $result = $this->cURL("Validate", "/v1/openapi/postalcodes", $text);
        if(!$result->status) {
            return $this->abort($result->response);
        }

        return $result->response->PostcodeList[0];
    }

	private function cURL($type = null, $function = null, $data = null, $additionals = null){
		$host = $this->host;
		$username = $this->username;
		$password = $this->password;
		$options = [
			'timeout' => 60,
			'http_errors' => false,
			'verify' => false
		];
        $method = 'POST';
		if($type == "token"){
			$host .= $this->url_token;

            $options['headers'] = [
				'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                'Content-Type' => 'application/x-www-form-urlencoded',
			];
			$options['form_params'] = [
				'grant_type' => 'client_credentials',
				'username' => $this->username,
				'password' => $this->password,
			];

        }
        else{
            $token = $this->get_token();
            $host .= $this->url . $function;

            $options = [];
            $options['headers'] = [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ];

            // if ($type == "with_auth_token") {
            //     $options['headers']['auth_token'] = $additionals['auth_token'];
            //     $options['headers']['referencedata'] = $additionals['referenceData'];
            // }
            if($type == "GET"){
                $method = 'GET';
            }
            else if($type == "Validate"){
                $host = $this->host . $function;
            }

            $postfield = $data;
            $options['body'] = $postfield;
        }
        $result = HttpClient::curl($method, $host, $options);
        
        if ($result->status) {
            if($type == "GET"){
                if($result->response == ''){
                    return (object)[
                        'status'=> $result->status,
                        'response' => $result->response
                    ];
                }
            }
            $json = json_decode($result->response);

            if (empty($json)) {
                $message = !empty($result->response) ? $result->response : __('api.empty_response', ['company' => $this->company]);

                return $this->abort($message);
            }
            $result->response = $json;
        } else {
            $message = !empty($result->response) ? $result->response : __('api.empty_response', ['company' => $this->company]);
            if(isset($result->response->status_code)){
                $message = $result->response->status_code;
            }
            return $this->abort($message);
        }
        return (object)$result;
	}
}