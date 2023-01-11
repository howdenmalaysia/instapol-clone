<?php

namespace App\Helpers\Insurer;

use App\DataTransferObjects\Motor\CartList;
use App\DataTransferObjects\Motor\OptionList;
use App\DataTransferObjects\Motor\ExtraCover;
use App\DataTransferObjects\Motor\VariantData;
use App\DataTransferObjects\Motor\Vehicle;
use App\DataTransferObjects\Motor\Response\ResponseData;
use App\DataTransferObjects\Motor\Response\PremiumResponse;
use App\DataTransferObjects\Motor\Response\VIXNCDResponse;
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
    private string $username;
	private string $password;
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 500000;
    private const OCCUPATION = '99';

    private const EXTRA_COVERAGE_LIST = ['89A', '112', '97A'];
    private const CART_AMOUNT_LIST = [50, 100, 200];
    private const CART_DAY_LIST = [7, 14, 21];

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
        $data = (object)[
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'vehicle_number' => $input->vehicle_number,
            'postcode' => $input->postcode
        ];
        
        $vix = $this->getVIXNCD($data);

        if(!$vix->status && is_string($vix->response)) {
            return $this->abort($vix->response);
        }

        $inception_date = $vix->response->polEffectiveDate;
        $expiry_date = $vix->response->polExpiryDate;
        
        $today = Carbon::today()->format('Y-m-d');
        // 1. Check inception date
        if($inception_date < $today) {
            return $this->abort('inception date expired');
        }

        // 2. Check Sum Insured -> market price
        $sum_insured = formatNumber($vix->response->nvicList[0]->vehicleMarketValue, 0);
        $sum_insured_type = "Makert Value";
        if ($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(
                __('api.sum_insured_referred_between', ['min_sum_insured' => self::MIN_SUM_INSURED, 'max_sum_insured' => self::MAX_SUM_INSURED]),
                config('setting.response_codes.sum_insured_referred')
            );
        }
        $nvic = explode('|', (string) $vix->response->nvicList[0]->nvic);
        //getting model
        $vehInputModel = (object)[      
            'makeCode' => $vix->response->makeCode,
            'modelCode' => $vix->response->modelCode,
        ];
        $variants = [];
        $BodyType = '';
        $uom = '';
        $VehModelCode = '';
        foreach($nvic as $_nvic) {
            // Get Vehicle Details
            $details = $this->allianzVariant($vehInputModel);
            $get_variant = $vix->response->nvicList[0]->vehicleVariant;
            foreach($details->response->VehicleList as $model_details){
                if(str_contains($model_details->Descp, $vix->response->nvicList[0]->vehicleVariant)){
                    $get_variant = $model_details->Descp;
                    $uom = $model_details->UOM;
                    $VehModelCode = $model_details->ModelCode;
                }
            }
            array_push($variants, new VariantData([
                'nvic' => $_nvic,
                'sum_insured' => floatval($sum_insured),
                'variant' => $get_variant,
            ]));
        }
        return (object) [
            'status' => true,
            'veh_model_code' => $VehModelCode,
            'uom' => $uom,
            'contractNumber' => $vix->response->contractNumber,
            'response' => new VIXNCDResponse([
                'body_type_code' => null,
                'body_type_description' => null,
                'chassis_number' => $vix->response->vehicleChassis,
                'coverage' => $vix->response->coverType,
                'engine_capacity' => intval($vix->response->vehicleEngineCC),
                'engine_number' => $vix->response->vehicleEngine,
                'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
                'inception_date' => Carbon::parse($inception_date)->format('d M Y'),
                'make' => $vix->response->vehicleMake,
                'make_code' => intval($vix->response->makeCode),
                'model' => $vix->response->vehicleModel,
                'model_code' => intval($vix->response->modelCode),
                'manufacture_year' => intval($vix->response->yearOfManufacture),
                'max_sum_insured' => doubleval(self::MAX_SUM_INSURED),
                'min_sum_insured' => doubleval(self::MIN_SUM_INSURED),
                'sum_insured' => $sum_insured,
                'sum_insured_type' => 'Market Value',
                'ncd_percentage' => floatval($vix->response->ncdPercentage),
                'seating_capacity' => intval($vix->response->seatingCapacity),
                'variants' => $variants,
                'vehicle_number' => $vix->response->vehicleLicenseId,
            ])
        ];
    }

    public function checkUBB(object $input) : object
    {  
        $postcode_details = $this->postalCode($input->postcode);
        $get_vehicle_details = (object)[
            'vehicle_number' => $input->vehicle_number,
            'id_type' => $this->id_type($input->id_type),
            'id_number' => $input->id_number,
            'postcode' => $postcode_details->Postcode,
        ];
        $vix = $this->vehicleDetails($get_vehicle_details);
        $get_avvariant = (object)[
            'region' => $postcode_details->Region,
            'makeCode' => $vix->response->make,
            'modelCode' => $vix->response->model,
            'makeYear' => $vix->response->manufacture_year, 
        ];
        $avvariant = $this->avVariant($get_avvariant)->response;
        $get_quotation = (object)[
            'input'=>$input,
            'vix'=>$vix,
            'avvariant'=>$avvariant,
        ];
        $quotation = $this->getQuotation($get_quotation)->response;
        $text = '{
            "ReferenceNo": "'.$quotation->contract->contractNumber.'",
            "ProductCat": "MT",
            "SourceSystem": "HOWDEN",
            "ClaimsExp": "0",
            "ReconInd": "N",
            "ExcessWaiveInd": "'.$quotation->contract->excessWaiveInd.'",
            "CheckUbbInd": 1,
            "Policy": {
                "PolicyEffectiveDate": "'.Carbon::parse($vix->response->inception_date)->format('Y-m-d').'",
                "PolicyExpiryDate": "'.Carbon::parse($vix->response->expiry_date)->format('Y-m-d').'",
                "Client": {
                    "IdentificationNumber": "'.$input->id_number.'",
                    "IdType": "NRIC",
                    "Age": "'.$input->age.'"
                },
                "RiskList": [{
                    "RiskId": "1",
                    "InsuredPerson": {
                        "IdentificationNumber": "'.$input->id_number.'",
                        "IdType": "NRIC"
                    },
                    "Vehicle": {
                        "AvCode": "'.$avvariant->VariantGrp[0]->AvCode.'",
                        "Capacity": "'.$vix->response->engine_capacity.'",
                        "MakeCode": "'.$vix->response->make_code.'",
                        "Model": "'.$vix->response->model.'",
                        "PiamModel": "28",
                        "Seat": '.$vix->response->seating_capacity.',
                        "VehicleNo": "'.$vix->response->vehicle_number.'",
                        "YearOfManufacture": "'.$vix->response->manufacture_year.'",
                        "NamedDriverList": [{
                            "Age": "'.$input->age.'",
                            "IdentificationNumber": "'.$input->id_number.'"
                        }],
                        "HighPerformanceInd": "'.$quotation->contract->highPerformanceInd.'",
                        "HrtvInd": "'.$quotation->contract->hrtvInd.'"
                    },
                    "CoverList": [{
                        "CoverPremium": {
                            "SumInsured": "'.$vix->response->sum_insured.'"
                        }
                    }]
                }]
            }
        }';
		$result = $this->cURL("getData", "/checkUBB", $text);
        
        if(!$result->status) {
            return $this->abort($result->response);
        }
        if(count($result->response->ReferRiskList) > 0){
            return new ResponseData([
                'status' => $result->status,
                'response' => $result->response// customer is eligible to purchase the insurance and can proceed with the subsequent quotation
            ]);
        }
        else{
            return new ResponseData([
                'status' => $result->status,
                'response' => $result->response
            ]);
        }
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

            if (empty($selected_variant)) {
                return $this->abort(trans('api.variant_not_match'));
            }

            // set vehicle
            $vehicle = new Vehicle([
                'make' => (string)$vehicle_vix->response->make,
                'model' => (string)$vehicle_vix->response->model,
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
                    'contractNumber' => $vehicle_vix->contractNumber,
                    'avvariant' => $avvariant
                ],
            ]);
            // get premium
            $get_quotation = (object)[
                'input'=>$input,
                'vix'=>$vehicle,
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

        // get premium
        $get_quotation = (object)[
            'input'=>$input,
            'vix'=>$vehicle,
        ];
        $motor_premium = $this->getQuotation($get_quotation);
        
        if (!$motor_premium->status) {
            return $this->abort($motor_premium->response);
        }

        $new_extracover_list = [];
        if(!empty($motor_premium->response->additionalCover)) {
            foreach($input->extra_cover as $extra_cover) {
                foreach($motor_premium->response->additionalCover as $extra) {
                    if((string) $extra->coverCode === $extra_cover->extra_cover_code) {
                        $extra_cover->premium = formatNumber((float) $extra->displayPremium);
                        $total_benefit_amount += (float) $extra->displayPremium;
                        $extra_cover->selected = floatval($extra->displayPremium) == 0;
    
                        if(!empty($extra->coverSumInsured)) {
                            $extra_cover->sum_insured = formatNumber((float) $extra->coverSumInsured);
                        }
                        array_push($new_extracover_list, $extra_cover);
                    }
                }
            }
        }
        $input->extra_cover = $new_extracover_list;

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
            'sum_insured' => formatNumber($vehicle->sum_insured ?? 0),
            'sum_insured_type' => $vehicle->sum_insured_type,
            'min_sum_insured' => formatNumber($vehicle->min_sum_insured),
            'max_sum_insured' => formatNumber($vehicle->max_sum_insured),
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
        $dobs = str_split($input->id_number, 2);
        $id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
        $year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}
		$dob = strval($year) . "-" . $dobs[1] . "-" . $dobs[2];
        $postcode_details = $this->postalCode($input->postcode);
        $get_vehicle_details = (object)[
            'vehicle_number' => $input->vehicle_number,
            'id_type' => $this->id_type($input->id_type),
            'id_number' => $input->id_number,
            'postcode' => $postcode_details->Postcode,
        ];
        $vix = $this->vehicleDetails($get_vehicle_details);
        $text = '{
            "salesChannel": "PTR",
            "contract": {
              "contractNumber": "'.$vix->contractNumber.'"
            },
            "person": {
              "identityType": "'.$this->id_type($input->id_type).'",
              "identityNumber": "'.$input->id_number.'",
              "fullName": "TAN AI LING",
              "birthDate": "'.$dob.'",
              "gender": "'.$input->gender.'",
              "email": "'.$input->email.'",
              "postalCode": "'.$postcode_details->Postcode.'",
              "mobilePrefix": 6012,
              "mobile": 23456789,
              "addressLine1": "'.$input->address_one.'",
              "addressLine2": "'.$input->address_two.'",
              "addressLine3": null
            },
            "vehicle": {
              "nvicCode": "'.$input->vehicle_number.'",
              "vehicleEngineCC": "'.$input->vehicle->engine_capacity.'",
              "yearOfManufacture": "'.$input->vehicle->manufacture_year.'",
              "occupantsNumber": '.$input->vehicle->extra_attribute->seating_capacity.'
            },
            "driverDetails": [
              {
                "fullName": "TAN AI LING",
                "identityNumber": "'.$input->id_number.'"
              }
            ],
            "payment": {
              "paymentMode": "ONLCCN",
              "paymentBankRef": 123456,
              "paymentId": 2,
              "paymentDate": "2018-12-06 09:36:19",
              "paymentAmount": "50.00"
            }
          }';

		$result = $this->cURL("getData", "/submission", $text);

        if(!$result->status) {
            return $this->abort($result->response);
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
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
		$dob = strval($year) . "-" . $dobs[1] . "-" . $dobs[2];
        $avcode = $qParams->vix->extra_attribute->avvariant->VariantGrp[0]->AvCode ?? '';
        $text = '{
            "partnerId": "PARTNERID",
            "contractNumber": "'.$qParams->vix->extra_attribute->contractNumber.'",
            "effectiveDate": "'.Carbon::parse($qParams->vix->inception_date)->format('Y-m-d').'",
            "expirationDate": "'.Carbon::parse($qParams->vix->expiry_date)->format('Y-m-d').'",
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
                "vehicleLicenseId": "'.$qParams->input->vehicle_number.'",
                "vehicleMake": "'.$qParams->vix->make.'",
                "vehicleModel": "'.$qParams->vix->model.'",
                "vehicleEngineCC": '.$qParams->vix->engine_capacity.',
                "yearOfManufacture": "'.$qParams->vix->manufacture_year.'",
                "occupantsNumber": '.$qParams->vix->extra_attribute->seating_capacity.',
                "ncdPercentage": '.$qParams->vix->ncd_percentage.',
                "sumInsured": "'.$qParams->vix->sum_insured.'",
                "avCode": "'.$avcode.'",
                "mvInd": "Y"
            }
        }';
		$result = $this->cURL("getData", "/quote", $text);
        if(!$result->status) {
            return $this->abort($result->response);
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
	}

    public function getVIXNCD(object $input) : object
    {
        $text = '{
            "sourceSystem": "HOWDEN",
            "vehicleLicenseId": "'.$input->vehicle_number.'",
            "identityType": "NRIC",
            "identityNumber": "'.$input->id_number.'",
            "checkUbbInd": "1",
            "postalCode": "'.$input->postcode.'"
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
    
    public function update_quotation(object $input) : object
    {
        $postcode_details = $this->postalCode($input->postcode);
        $get_vehicle_details = (object)[
            'vehicle_number' => $input->vehicle_number,
            'id_type' => "NRIC",
            'id_number' => $input->id_number,
            'postcode' => $postcode_details->Postcode,
        ];
        $vix = $this->vehicleDetails($get_vehicle_details);
        $get_avvariant = (object)[
            'region' => $postcode_details->Region,
            'makeCode' => $vix->response->make,
            'modelCode' => $vix->response->model,
            'makeYear' => $vix->response->manufacture_year,
        ];
        $avvariant = $this->avVariant($get_avvariant)->response;
        // "contractNumber": "'.$vix->contractNumber.'",
        $text = '{
            "salesChannel": "PTR",
            "partnerId": "AZOL",
            "contractNumber": "CNAZ00002325377",
            "effectiveDate": "'.Carbon::parse($vix->response->inception_date)->format('Y-m-d').'",
            "expirationDate": "'.Carbon::parse($vix->response->expiry_date)->format('Y-m-d').'",
            "additionalCover": [
              {
                "coverCode": "72",
                "coverSumInsured": 0
              }
            ],
            "calculateDiscount": {
              "discountPercentage": ""
            },
            "unlimitedDriverInd": false,
            "driverDetails": [
              {
                "fullName": "TAN AI LING",
                "identityNumber": "'.$input->id_number.'"
              }
            ],
            "vehicle": {
              "avCode": ""
            }
          }';

		$result = $this->cURL("update", "/quote", $text);

        if(!$result->status) {
            return $this->abort($result->response);
        }
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
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

    private function id_type(string $IDType)
    {
        $result = '';
        switch ($IDType) {
            case '1': {
                $result = "NRIC";
                break;
            }
            case '2': {
                $result = "OLD_IC";
                break;
            }
            case '3': {
                $result = "PASS";
                break;
            }
            case '4': {
                $result = "POL";
                break;
            }
        }

        return $result;
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

            if($type == "GET"){
                $method = 'GET';
            }
            else if($type == 'update'){
                $method = 'PUT';
            }
            else if($type == 'Validate'){
                $host = $this->host . $function;
            }

            $postfield = $data;
            $options['body'] = $postfield;
        }
        
        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $this->url,
            'path' => $function,
            'request_header' => json_encode($options['headers']),
            'request' => json_encode($options['body'] ?? $options['form_params']),
        ]);

        $result = HttpClient::curl($method, $host, $options);

        // Update the API log
        APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => $result->response
            ]);

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