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
use Illuminate\Support\Facades\Log;

class Allianz implements InsurerLibraryInterface
{
    private string $username;
	private string $password;
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 500000;
    private const ADJUSTMENT_RATE_UP = 10;
    private const ADJUSTMENT_RATE_DOWN = 10;
    private const OCCUPATION = '99';

    private const EXTRA_COVERAGE_LIST = ['PAB-ERW','72','89','97A','101','102','25','100(a)',
    'A202','57','111','112','109','A206','A209','A201','PAB3'];
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
        //checking blacklisted car
        if(isset($vix->response->UBBStatus)){
            if($vix->response->UBBStatus->ReferRiskList[0]->ReferCode  == "RP007"){
                return $this->abort(__('api.allianz_error'), config('setting.response_codes.blacklisted_vehicle'));
            }
            else{
                return $this->abort(json_encode($vix->response->UBBStatus));
            }
        }
        if(isset($vix->response->errors)){
            return $this->abort(json_encode($vix->response->errors));
        }

        if(empty($vix->response->nvicList)){
            return $this->abort('Empty nvicList!');
        }
        $inception_date = $vix->response->polEffectiveDate;
        $expiry_date = $vix->response->polExpiryDate;
        
        $today = Carbon::today()->format('Y-m-d');
        // 1. Check inception date
        if($inception_date < $today) {
            return $this->abort('inception date expired');
        }
        else {
            // Check 2 Months Before
            if (Carbon::parse($today)->addMonths(2)->lessThan($inception_date)) {
                return $this->abort(__('api.earlier_renewal'), config('setting.response_codes.earlier_renewal'));
            }
        }
        // 2. Check Sum Insured -> market price
        $sum_insured = formatNumber($vix->response->nvicList[0]->vehicleMarketValue, 0);
        if($sum_insured < self::MIN_SUM_INSURED || roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true) > self::MAX_SUM_INSURED) {
            return $this->abort(__('api.sum_insured_referred_between', [
                'min_sum_insured' => self::MIN_SUM_INSURED,
                'max_sum_insured' => self::MAX_SUM_INSURED
            ]), config('setting.response_codes.sum_insured_referred'));
        }
        
        if ($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(
                __('api.sum_insured_referred_between', ['min_sum_insured' => self::MIN_SUM_INSURED, 'max_sum_insured' => self::MAX_SUM_INSURED]),
                config('setting.response_codes.sum_insured_referred')
            );
        }
        if(empty($vix->response->nvicList)){
            return $this->abort('Getting empty nvic List!');
        }
        else{
            $nvic = $vix->response->nvicList;
        }

        //get avmodel
        $vehicleModel = $vix->response->vehicleModel;
        if($vix->response->vehicleModel == 'COROLLA' || $vix->response->vehicleModel == 'ALTIS'){
            $vehicleModel = 'COROLLA/ALTIS';
        }
        $get_avmodel = (object)[
            'region' => $input->region,
            'makeCode' => $vix->response->avMakeCode,
            'modelCode' => $vehicleModel,
        ];
        $getavmodel = $this->avModel($get_avmodel);
        if (!$getavmodel->status) {
            return $this->abort($getavmodel->response);
        }
        //get avvariant
        $get_avvariant = (object)[
            'region' => $input->region,
            'makeCode' => $vix->response->avMakeCode,
            'modelCode' => $vehicleModel,
            'makeYear' => $vix->response->yearOfManufacture,
        ];
        $avvariant = $this->avVariant($get_avvariant);
        if (!$avvariant->status) {
            return $this->abort($avvariant->response);
        }
        //getting variant
        $variants = [];
        $variant_list = [];
        $BodyType = '';
        $uom = '';
        $VehModelCode = '';
        $get_variant = $avvariant->response->VariantGrp[0]->Variant;
        $AvCode = $avvariant->response->VariantGrp[0]->AvCode;
        $sum_insured = (double)$avvariant->response->VariantGrp[0]->SumInsured;
        if(isset($input->variant) && ! empty($input->variant)){
            foreach($avvariant->response->VariantGrp as $variantlist){
                array_push($variant_list,$variantlist->Variant);
                if($input->variant == $variantlist->Variant){
                    $get_variant = $variantlist->Variant;
                    $sum_insured = formatNumber($variantlist->SumInsured, 0);
                    $AvCode = $variantlist->AvCode;
                }
            }
        }
        foreach($nvic as $_nvic) {
            array_push($variants, new VariantData([
                'nvic' => $_nvic->nvic,
                'sum_insured' => floatval($_nvic->vehicleMarketValue),
                'variant' => $_nvic->vehicleVariant
            ]));
        }
        $set_vehicle = (object)[
            'make' => (string)$vix->response->vehicleMake,
            'model' => (string)$vix->response->vehicleModel,
            'nvic' => $variants[0]->nvic ?? $input->nvic,
            'variant' => $get_variant,
            'engine_capacity' => intval($vix->response->vehicleEngineCC),
            'manufacture_year' => intval($vix->response->yearOfManufacture),
            'ncd_percentage' => floatval($vix->response->ncdPercentage),
            'coverage' => $vix->response->coverType,
            'inception_date' => Carbon::parse($inception_date)->format('Y-m-d'),
            'expiry_date' => Carbon::parse($expiry_date)->format('Y-m-d'),
            'sum_insured_type' => 'Agreed Value',
            'sum_insured' => $sum_insured,
            'min_sum_insured' => $sum_insured,
            'max_sum_insured' => $sum_insured,
            'extra_attribute' => (object) [
                'chassis_number' => $vix->response->vehicleChassis,
                'cover_type' => $vix->response->coverType,
                'engine_number' => $vix->response->vehicleEngine,
                'seating_capacity' => intval($vix->response->seatingCapacity),
                'contractNumber' => $vix->response->contractNumber,
                'make_code' => intval($vix->response->makeCode),
                'model_code' => intval($vix->response->modelCode),
                'avMakeCode' => $vix->response->avMakeCode,
                'AvCode' => $AvCode,
            ],
        ];
        $get_quotation = (object)[
            'input'=>$input->input ?? $input,
            'vix'=>$set_vehicle,
        ];
        $motor_premium = $this->getQuotation($get_quotation);
        if (!$motor_premium->status) {
            return $this->abort($motor_premium->response);
        }
        return (object) [
            'status' => true,
            'veh_model_code' => $VehModelCode,
            'uom' => $uom,
            'contractNumber' => $vix->response->contractNumber,
            'avMakeCode' => $vix->response->avMakeCode,
            'AvCode' => $AvCode,
            'cover_type' => $vix->response->coverType,
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
                'max_sum_insured' => $sum_insured,
                'min_sum_insured' => $sum_insured,
                'sum_insured' => $sum_insured,
                'sum_insured_type' => 'Agreed Value',
                'ncd_percentage' => floatval($vix->response->ncdPercentage),
                'seating_capacity' => intval($vix->response->seatingCapacity),
                'variants' => $variants,
                'vehicle_number' => $vix->response->vehicleLicenseId,
            ])
        ];
    }

    public function checkUBB(object $input) : object
    {
        $text = '{
            "ReferenceNo": "'.$input->contractNumber.'",
            "ProductCat": "MT",
            "SourceSystem": "PARTNER_ID",
            "ClaimsExp": 0,
            "ReconInd": "N",
            "ExcessWaiveInd": "'.$input->excessWaiveInd.'",
            "CheckUbbInd": 2,
            "Policy": {
                "PolicyEffectiveDate": "'.$input->inception_date.'",
                "PolicyExpiryDate": "'.$input->expiry_date.'",
                "Client": {
                    "IdentificationNumber": '.$input->id_number.',
                    "IdType": "'.$input->id_type.'",
                    "Age": '.$input->age.'
                },
                "RiskList": [{
                    "RiskId": 1,
                    "InsuredPerson": {
                        "IdentificationNumber": '.$input->id_number.',
                        "IdType": "NRIC"
                    },
                    "Vehicle": {
                        "AvCode": "'.$input->AvCode.'",
                        "Capacity": '.$input->Capacity.',
                        "MakeCode": '.$input->MakeCode.',
                        "Model": "'.$input->Model.'",
                        "PiamModel": 28,
                        "Seat": '.$input->Seat.',
                        "VehicleNo": "'.$input->VehicleNo.'",
                        "YearOfManufacture": '.$input->YearOfManufacture.',
                        "NamedDriverList": [{
                            "Age": '.$input->age.',
                            "IdentificationNumber": '.$input->id_number.'
                        }],
                        "HighPerformanceInd": "'.$input->HighPerformanceInd.'",
                        "HrtvInd": "'.$input->HrtvInd.'"
                    },
                    "CoverList": [{
                        "CoverPremium": {
                            "SumInsured": '.$input->SumInsured.'
                        }
                    }]
                }]
            }
        }';
		$result = $this->cURL("getData", "checkUBB", $text);
        
        if(!$result->status) {
            return $this->abort($result->response);
        }
        else{
            if(empty($result->response->ReferRiskList)){
                return new ResponseData([
                    'status' => $result->status,
                    'response' => $result->response
                ]);
            }
            else{
                return $this->abort(json_encode($result->response->ReferRiskList));
            }
        }
    }

    public function allianzMake(object $input) : object
    {
        $make_code = "";
        if(isset($input->makeCode) && $input->makeCode != ''){
            $make_code = "?makeCode=".$input->makeCode;
        }
        $function = 'allianzMake';

		$result = $this->cURL("GET", "lov/allianzMake".$make_code);
		
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
		$result = $this->cURL("GET", "lov/allianzModel".$make_code.$model_code);

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
		$result = $this->cURL("GET", "lov/allianzVariant".$make_code.$model_code);

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
		$result = $this->cURL("GET", "lov/avMake".$region.$make_code);

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
        $region = 'W';
        if(strtoupper($input->region) == 'WEST'){
            $region = 'W';
        }
        else if(strtoupper($input->region) == 'EAST'){
            $region = 'E';
        }
        else if(strtoupper($input->region) == 'W'){
            $region = 'W';
        }
        else if(strtoupper($input->region) == 'E'){
            $region = 'E';
        }
        $region = "?region=".$region;
        $make_code = "&makeCode=".$input->makeCode;
        $model_code = "";
        if(isset($input->modelCode) && $input->modelCode != ''){
            $model_code = "&modelCode=".$input->modelCode;
        }
        $function = 'avModel';//CITY
		$result = $this->cURL("GET", "lov/avModel".$region.$make_code.$model_code);
        
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
        $region = 'W';
        if(strtoupper($input->region) == 'WEST'){
            $region = 'W';
        }
        else if(strtoupper($input->region) == 'EAST'){
            $region = 'E';
        }
        else if(strtoupper($input->region) == 'W'){
            $region = 'W';
        }
        else if(strtoupper($input->region) == 'E'){
            $region = 'E';
        }
        $region = "?region=".$region;
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
		$result = $this->cURL("GET", "lov/avVariant".$region.$make_code.$model_code.$make_year);
		
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

    public function getVariant(object $input):object 
    {
        $postcode_details = $this->postalCode($input->postcode);
        $get_vehicle_details = (object)[
            'vehicle_number' => $input->vehicle_number,
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'postcode' => $postcode_details->Postcode,
        ];
        
        $vix = $this->getVIXNCD($get_vehicle_details);

        if(!$vix->status && is_string($vix->response)) {
            return $this->abort($vix->response);
        }
        if(empty($vix->response->nvicList)){
            return $this->abort('Empty nvicList!');
        }
        $vehicleModel = $vix->response->vehicleModel;
        if($vix->response->vehicleModel == 'COROLLA' || $vix->response->vehicleModel == 'ALTIS'){
            $vehicleModel = 'COROLLA/ALTIS';
        }
        
        $get_avvariant = (object)[
            'region' => $postcode_details->Region,
            'makeCode' => $vix->response->avMakeCode,
            'modelCode' => $vehicleModel,
            'makeYear' => $vix->response->yearOfManufacture,
        ];
        $avvariant = $this->avVariant($get_avvariant)->response;

        return (object) ['status' => true, 'response' => $avvariant->VariantGrp];
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
                'id_type' => $input->id_type,
                'id_number' => $input->id_number,
                'postcode' => $postcode_details->Postcode,
                'region' => $postcode_details->Region,
                'variant' => $input->vehicle->variant,
                'nvic' => $input->nvic,
                'input' => $input,
            ];
            $vehicle_vix = $this->vehicleDetails($get_vehicle_details);
            if (!$vehicle_vix->status) {
                return $this->abort($vehicle_vix->response, $vehicle_vix->code);
            }
            
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
                'inception_date' => Carbon::createFromFormat('d M Y', $vehicle_vix->response->inception_date)->format('Y-m-d'),
                'expiry_date' => Carbon::createFromFormat('d M Y', $vehicle_vix->response->expiry_date)->format('Y-m-d'),
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
                    'make_code' => $vehicle_vix->response->make_code,
                    'model_code' => $vehicle_vix->response->model_code,
                    'avMakeCode' => $vehicle_vix->avMakeCode,
                    'AvCode' => $vehicle_vix->AvCode,
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
            $total_payable = formatNumber($motor_premium->response->premium->premiumDueRoundedAfterPTV);
            $net_premium = formatNumber($motor_premium->response->premium->premiumDueRoundedAfterPTV - $motor_premium->response->premium->commissionAmount);

            // Remove Extra Cover which is not entitled
            $available_benefits = self::EXTRA_COVERAGE_LIST;
            switch($input->id_type) {
				case '1': {
					$available_benefits = array_filter($available_benefits, function ($benefits) {
						return $benefits != 'A201' && $benefits != 'PAB3';
					});
					break;
				}
			}
            $extra_cover_list = [];
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
                    'plan_type' => ''
                ]);

                switch($extra_cover_code) {
                    case '89': {
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
                    case 'PAB-ERW': {
                        $option_list = new OptionList([
                            'name' => 'sum_insured',
                            'description' => 'Option List',
                            'values' => ['Plan A', 'Plan B', 'Plan C'],
                            'any_value' => true,
                            'increment' => null
                        ]);
                        $item->option_list = $option_list;

                        // Default to RM 1,000
                        $item->plan_type = $option_list->values[0];
                        break;
                    }
                    case 'PAB3': {
                        $option_list = new OptionList([
                            'name' => 'sum_insured',
                            'description' => 'Option List',
                            'values' => ['Plan A', 'Plan B'],
                            'any_value' => true,
                            'increment' => null
                        ]);
                        $item->option_list = $option_list;

                        // Default to RM 1,000
                        $item->plan_type = $option_list->values[0];
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
                array_push($extra_cover_list, $item);
            }
            // Include Extra Covers to Get Premium
            $input->extra_cover = $extra_cover_list;
        }

        // get premium
        $get_quotation = (object)[
            'input'=>$input,
            'vix'=>$vehicle,
        ];
        $motor_premium = $this->update_quotation($get_quotation);
        if (!$motor_premium->status) {
            return $this->abort($motor_premium->response);
        }

        $new_extracover_list = [];
        if(!empty($motor_premium->response->additionalCover)) {
            foreach($input->extra_cover as $extra_cover) {
                foreach($motor_premium->response->additionalCover as $extra) {
                    if((string) $extra->coverCode === $extra_cover->extra_cover_code) {
                        $extra_cover->premium = formatNumber((float) $extra->displayPremium);
                        $total_benefit_amount += (float)$extra->displayPremium;
                        $extra_cover->selected = (float)$extra->displayPremium == 0;

                        if(!empty($extra->coverSumInsured)) {
                            $extra_cover->sum_insured = formatNumber((float) $extra->coverSumInsured);
                        }
                        
                        if($extra_cover->premium > 0){
                            array_push($new_extracover_list, $extra_cover);
                        }
                    }
                }
            }
        }
        $input->extra_cover = $new_extracover_list;
        //get additional driver amount
        $additional_driver_amount = 0;
        if(isset($motor_premium->response->unlimitedDriverInfo->amount)){
            $additional_driver_amount = $motor_premium->response->unlimitedDriverInfo->amount;
        }
        $response = new PremiumResponse([
            'basic_premium' => formatNumber($motor_premium->response->premium->basicPremium),
            'ncd_percentage' => $motor_premium->response->premium->ncdPct,
            'ncd_amount' => formatNumber($motor_premium->response->premium->ncdAmt),
            'total_benefit_amount' => formatNumber($total_benefit_amount + $additional_driver_amount),
            'gross_premium' => formatNumber($motor_premium->response->premium->grossPremium),
            'sst_percent' => formatNumber($motor_premium->response->premium->serviceTaxPercentage),
            'sst_amount' => formatNumber($motor_premium->response->premium->serviceTaxAmount),
            'stamp_duty' => formatNumber($motor_premium->response->premium->stampDuty),
            'excess_amount' => formatNumber($motor_premium->response->premium->excessAmount),
            'total_payable' => formatNumber($motor_premium->response->premium->premiumDueRoundedAfterPTV),
            'net_premium' => formatNumber($motor_premium->response->premium->premiumDueRoundedAfterPTV - $motor_premium->response->premium->commissionAmount),
            'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
            'personal_accident' => $pa,
            'sum_insured' => (double)$vehicle->sum_insured,
            'sum_insured_type' => $vehicle->sum_insured_type,
            'min_sum_insured' => (double)$vehicle->min_sum_insured,
            'max_sum_insured' => (double)$vehicle->max_sum_insured,
            'named_drivers_needed' => true,
            'contract_number' => $vehicle->extra_attribute->contractNumber,
        ]);

        if ($full_quote) {
            // Revert to premium without extra covers
            $response->basic_premium = $basic_premium;
            $response->ncd_percentage = $ncd_percentage;
            $response->ncd_amount = $ncd_amount;
            $response->total_benefit_amount = 0;
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

    private function sortExtraCoverList(array $extra_cover_list) : array
    {
        foreach ($extra_cover_list as $_extra_cover) {
            $sequence = 99;
            switch ((array)$_extra_cover->extra_cover_code) {
                case 'PAB-ERW': { // Motor Enhanced Road Warrior
                    $sequence = 1;
                    break;
                }
                case '72': { // Legal Liability Of Passengers for Negligent Acts
                    $sequence = 2;
                    break;
                }
                case '89': { // Cover for Windscreens, Windows And Sunroof
                    $sequence = 4;
                    break;
                }
                case '97A': { // Gas Conversion Kit and Tank
                    $sequence = 5;
                    break;
                }
                case '101': { // Extension of Cover To The Kingdom of Thailand
                    $sequence = 6;
                    break;
                }
                case '102': { // Extension of Cover to Kalimantan
                    $sequence = 7;
                    break;
                }
                case '25': { // Strike , Riot , and Civil Commotion
                    $sequence = 8;
                    break;
                }
                case '100(a)': { // Legal Liability to Passengers
                    $sequence = 9;
                    break;
                }
                case 'A202': { // Private Hire Car (e-Hailing)
                    $sequence = 10;
                    break;
                }
                case '57': { // Inclusion of Special Perils
                    $sequence = 11;
                    break;
                }
                case '11': { // Current Year "NCD" Relief
                    $sequence = 12;
                    break;
                }
                case '112': { // Compensation For Assessed Repair Time (CART)
                    $sequence = 13;
                    break;
                }
                case '109': { // Sabah Ferry Transit
                    $sequence = 14;
                    break;
                }
                case 'A209': { // Car Break-In/Robbery
                    $sequence = 16;
                    break;
                }
                case 'A206': { // Key Care
                    $sequence = 17;
                    break;
                }
                case 'A201': {
                    $sequence = 18;
                    break;
                }
                case 'PAB3': {
                    $sequence = 19;
                    break;
                }
            }
            
            $_extra_cover->sequence = $sequence;
        }
        $sorted = array_values(Arr::sort($extra_cover_list, function ($value) {
            return $value->sequence;
        }));

        return $sorted;
    }

    private function getExtraCoverDescription(string $extra_cover_code) : string
    {
        $extra_cover_name = '';

        switch($extra_cover_code) {
            case 'PAB-ERW': { 
                $extra_cover_name = 'Motor Enhanced Road Warrior';
                break;
            }
            case '72': { 
                $extra_cover_name = 'Legal Liability Of Passengers for Negligent Acts';
                break;
            }
            case '89': { 
                $extra_cover_name = 'Cover for Windscreens, Windows And Sunroof';
                break;
            }
            case '97A': { 
                $extra_cover_name = 'Gas Conversion Kit and Tank';
                break;
            }
            case '101': { 
                $extra_cover_name = 'Extension of Cover To The Kingdom of Thailand';
                break;
            }
            case '102': { 
                $extra_cover_name = 'Extension of Cover to Kalimantan';
                break;
            }
            case '25': { 
                $extra_cover_name = 'Strike , Riot , and Civil Commotion';
                break;
            }
            case '100(a)': { 
                $extra_cover_name = 'Legal Liability to Passengers';
                break;
            }
            case 'A202': { 
                $extra_cover_name = 'Private Hire Car (e-Hailing)';
                break;
            }
            case '57': { 
                $extra_cover_name = 'Inclusion of Special Perils';
                break;
            }
            case '111': { 
                $extra_cover_name = 'Current Year "NCD" Relief';
                break;
            }
            case '112': { 
                $extra_cover_name = 'Compensation For Assessed Repair Time (CART)';
                break;
            }
            case '109': { 
                $extra_cover_name = 'Sabah Ferry Transit';
                break;
            }
            case 'A206': { 
                $extra_cover_name = 'Key Care';
                break;
            }
            case 'A209': { 
                $extra_cover_name = 'Car Break-In/Robbery';
                break;
            }
            case 'A201': { 
                $extra_cover_name = 'Waiver of Betterment Contribution';
                break;
            }
            case 'PAB3': { 
                $extra_cover_name = 'Driver and Passengers Shield';
                break;
            }
        }

        return $extra_cover_name;
    }

    public function submission(object $input) : object
    {
        $dobs = explode(" ", $input->insurance->holder->date_of_birth);
        $dob = $dobs[0];
        $phone_code = $input->insurance->holder->phone_code;
        $phone_number = $input->insurance->holder->phone_number;
        $phone = str_split($input->insurance->holder->phone_number, 2);
        $phone_prefix = $phone_code . $phone[0];
        $mobile = str_replace($phone[0],"",$phone_number);
        //check driver
        $e_hailing = false;
        foreach($input->insurance->extra_cover as $value){
            if($value == 'A202'){
                $e_hailing = true;
            }
        }
        if($e_hailing){
            if($input->insurance_motor->number_of_drivers >= 3){
                $driver = '"driverDetails": [],';
            }
            else{
                $driver = '"driverDetails": [{
                    "fullName":"'.$input->insurance->holder->name.'",
                    "identityNumber":'.$input->insurance->holder->id_number.',
                    "driverType":"EHAIL"
                    }';
                foreach($input->insurance_motor->driver as $value){
                    $driver .= ',{
                        "fullName":"'.$value->name.'",
                        "identityNumber":'.$value->id_number.',
                        "driverType":"EHAIL"
                        }';
                }
                $driver .= '],';
            }
        }
        else{
            if($input->insurance_motor->number_of_drivers >= 3){
                $driver = '"driverDetails": [],';
            }
            else{
                $driver = '"driverDetails": [{
                    "fullName":"'.$input->insurance->holder->name.'",
                    "identityNumber":'.$input->insurance->holder->id_number.'
                    }';
                foreach($input->insurance_motor->driver as $value){
                    $driver .= ',{
                        "fullName":"'.$value->name.'",
                        "identityNumber":'.$value->id_number.'
                        }';
                }
                $driver .= '],';
            }
        }
        $text = '{
            "salesChannel": "PTR",
            "contract": {
              "contractNumber": "'.$input->insurance->contract_number.'"
            },
            "person": {
              "identityType": "'.$this->id_type($input->insurance->holder->id_type_id).'",
              "identityNumber": '.$input->insurance->holder->id_number.',
              "fullName": "'.$input->insurance->holder->name.'",
              "birthDate": "'.$dob.'",
              "gender": "'.$input->insurance->holder->gender.'",
              "email": "'.$input->insurance->holder->email_address.'",
              "postalCode": "'.$input->insurance->address->postcode.'",
              "mobilePrefix": '.$phone_prefix.',
              "mobile": '.$mobile.',
              "addressLine1": "'.$input->insurance->address->address_one.'",
              "addressLine2": "'.$input->insurance->address->address_two.'",
              "addressLine3": "'.$input->insurance->address->city.'"
            },
            "vehicle": {
              "nvicCode": "'.$input->insurance_motor->nvic.'",
              "vehicleEngineCC": '.$input->insurance_motor->engine_capacity.',
              "yearOfManufacture": '.$input->insurance_motor->manufactured_year.',
              "occupantsNumber": '.$input->insurance_motor->seating_capacity.'
            },'
            .$driver.
            '"payment": {
              "paymentMode": "",
              "paymentBankRef": ,
              "paymentId": "",
              "paymentDate": "'.$input->payment_date.'",
              "paymentAmount": '.$input->payment_amount.'"
            }
          }';
		$result = $this->cURL("getData", "submission", $text);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        
        $response = (object) [
            'contract_number' => $input->insurance->contract_number
        ];

        return (object) ['status' => true, 'response' => $response];
    }

    public function abort(string $message, int $code = 490) : ResponseData
    {
        return new ResponseData([
            'status' => false,
            'response' => $message,
            'code' => $code
        ]);
    }

    public function quotation(object $input) : object
    {
        $data = (object) [
            'age' => $input->age,
            'additional_driver' => $input->additional_driver,
            'email' => $input->email,
            'extra_cover' => $input->extra_cover,
            'gender' => $input->gender,
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'marital_status' => $input->marital_status,
            'postcode' => $input->postcode,
            'region' => $input->region,
            'state' => $input->state,
            'vehicle' => $input->vehicle,
            'vehicle_number' => $input->vehicle_number,
        ];

        $quotation = $this->premiumDetails($data);

        if(!$quotation->status) {
            return $this->abort($quotation->response);
        }

        return (object) [
            'status' => true,
            'response' => new PremiumResponse([
                'act_premium' => $quotation->response->act_premium,
                'basic_premium' => $quotation->response->basic_premium,
                'excess_amount' => $quotation->response->excess_amount,
                'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
                'gross_premium' => $quotation->response->gross_premium,
                'ncd_amount' => $quotation->response->ncd_amount,
                'net_premium' => $quotation->response->net_premium,
                'sst_amount' => $quotation->response->sst_amount,
                'sst_percent' => $quotation->response->sst_percent,
                'stamp_duty' => $quotation->response->stamp_duty,
                'total_benefit_amount' => $quotation->response->total_benefit_amount,
                'total_payable' => $quotation->response->total_payable,
                'request_id' => $quotation->response->request_id,
                'loading' => $quotation->response->loading,
                'sum_insured' => $quotation->response->sum_insured,
                'sum_insured_type' => $input->vehicle->sum_insured_type,
                'min_sum_insured' => floatval($quotation->response->min_sum_insured),
                'max_sum_insured' => floatval($quotation->response->max_sum_insured),
                'named_drivers_needed' => false,
                'contract_number' => $quotation->response->contract_number,
            ])
        ];
    }

    public function getQuotation(object $qParams) : object
    {
        if($qParams->input->id_type == '1'){
            $dobs = str_split($qParams->input->id_number, 2);
            $id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
            $year = intval($dobs[0]);
            if ($year >= 10) {
                $year += 1900;
            } else {
                $year += 2000;
            }
            $dob = strval($year) . "-" . $dobs[1] . "-" . $dobs[2];
            $id_type = 'NRIC';
            $age = getAgeFromIC($qParams->input->id_number);
		}
		else if($qParams->input->id_type == '4'){
            $id_type = 'OLD_IC';
            $dob = '1984-11-03';//cannot be empty
            $age = '';
		}
        //check avcode selected or default first variant's avcode
        $avcode = $qParams->input->vehicle->extra_attribute->AvCode ?? '';
        if(empty($avcode)){
            $avcode = $qParams->vix->extra_attribute->AvCode;
        }
        $SumInsured = $qParams->input->vehicle->sum_insured ?? '';
        if(empty($SumInsured)){
            $SumInsured = $qParams->vix->sum_insured;
        }
        $contractNumber = $qParams->vix->extra_attribute->contractNumber;
        $text = '{
            "partnerId": "HOWDEN",
            "contractNumber": "'.$contractNumber.'",
            "effectiveDate": "'.Carbon::parse($qParams->vix->inception_date)->format('Y-m-d').'",
            "expirationDate": "'.Carbon::parse($qParams->vix->expiry_date)->format('Y-m-d').'",
            "person": {
                "identityType": "'.$id_type.'",
                "identityNumber": '.$qParams->input->id_number.',
                "gender": "'.$qParams->input->gender.'",
                "birthDate": "'.$dob.'",
                "maritalStatus": '.$this->getMaritalStatusCode(! empty($qParams->input->marital_status) ? $qParams->input->marital_status : 'S').',
                "postalCode": '.$qParams->input->postcode.',
                "noOfClaims": 0
            },
            "calculateDiscount": {
                "discountPercentage": 0
            },
            "vehicle": {
                "vehicleLicenseId": "'.$qParams->input->vehicle_number.'",
                "vehicleMake": '.$qParams->vix->extra_attribute->make_code.',
                "vehicleModel": '.$qParams->vix->extra_attribute->model_code.',
                "vehicleEngineCC": '.$qParams->vix->engine_capacity.',
                "yearOfManufacture": '.$qParams->vix->manufacture_year.',
                "occupantsNumber": '.$qParams->vix->extra_attribute->seating_capacity.',
                "ncdPercentage": '.$qParams->vix->ncd_percentage.',
                "sumInsured": '.$SumInsured.',
                "avCode": "'.$avcode.'",
                "mvInd": "N"
            }
        }';

		$result = $this->cURL("getData", "quote", $text);
        if(!$result->status) {
            return $this->abort($result->response);
        }
        //check UBB
        $ubb = (object)[
            'contractNumber' => $contractNumber,
            'excessWaiveInd' => $result->response->contract->excessWaiveInd,
            'inception_date' => Carbon::parse($qParams->vix->inception_date)->format('Y-m-d'),
            'expiry_date' => Carbon::parse($qParams->vix->expiry_date)->format('Y-m-d'),
            'id_number' => $qParams->input->id_number,
            'id_type' => $id_type,
            'age' => $age,
            'AvCode' => $avcode,
            'Capacity' => $qParams->vix->engine_capacity,
            'MakeCode' => $qParams->vix->extra_attribute->make_code,
            'Model' => $qParams->vix->model,
            'Seat' => $qParams->vix->extra_attribute->seating_capacity,
            'VehicleNo' => $qParams->input->vehicle_number,
            'YearOfManufacture' => $qParams->vix->manufacture_year,
            'HighPerformanceInd' => $result->response->contract->highPerformanceInd,
            'HrtvInd' => $result->response->contract->hrtvInd,
            'SumInsured' => $SumInsured,
        ];
        $checkUBB = $this->checkUBB($ubb);
         
        if(!$checkUBB->status) {
            return $this->abort($checkUBB->response);
        }
        else{
            if(!empty($checkUBB->response->ReferRiskList)){
                return $this->abort(json_encode($checkUBB->response->ReferRiskList));
            }
        }
        
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
	}

    public function getVIXNCD(object $input) : object
    {
        if($input->id_type == '1') {
            $id_type = 'NRIC';
		}
		else if ($input->id_type == '4') {
            $id_type = 'OLD_IC';
		}
        $text = '{
            "sourceSystem": "PARTNER_ID",
            "vehicleLicenseId": "'.$input->vehicle_number.'",
            "identityType": "'.$id_type.'",
            "identityNumber": '.$input->id_number.',
            "checkUbbInd": 1,
            "postalCode": '.$input->postcode.'
        }';
        $result = $this->cURL("getData", "vehicleDetails", $text);
        
        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
    }
    
    public function update_quotation(object $input) : object
    {
        $additional_cover = null;
        if(!empty($input->input->extra_cover)){
            $count_ec = count($input->input->extra_cover);
            $index = 1;
            foreach($input->input->extra_cover as $extra_cover){
                $coverCode = $extra_cover->coverCode ?? $extra_cover->extra_cover_code;
                $coverSumInsured = $extra_cover->sum_insured;
                if($index == $count_ec){
                    if($coverCode == 'PAB-ERW'){
                        $plan_type = $extra_cover->plan_type ?? $extra_cover->sum_insured;
                        if($plan_type == 'Plan A'){
                            $planCode = "PABERWA";
                        }
                        else if($plan_type == 'Plan B'){
                            $planCode = "PABERWB";
                        }
                        else if($plan_type == 'Plan C'){
                            $planCode = "PABERWC";
                        }
                        $additional_cover .= '{
                            "coverCode": "'.$coverCode.'",
                            "planCode": "'.$planCode.'"
                        }';
                    }
                    else if($coverCode == 'PAB3'){
                        $plan_type = $extra_cover->plan_type ?? $extra_cover->sum_insured;
                        if($plan_type == 'Plan A'){
                            $planCode = "PAB3A";
                        }
                        else if($plan_type == 'Plan B'){
                            $planCode = "PAB3B";
                        }
                        $additional_cover .= '{
                            "coverCode": "'.$coverCode.'",
                            "planCode": "'.$planCode.'"
                        }';
                    }
                    else{
                        $additional_cover .= '{
                            "coverCode": "'.$coverCode.'",
                            "coverSumInsured": "'.$coverSumInsured.'"
                        }';
                    }
                }
                else{
                    if($coverCode == 'PAB-ERW'){
                        $plan_type = $extra_cover->plan_type ?? $extra_cover->sum_insured;
                        if($plan_type == 'Plan A'){
                            $planCode = "PABERWA";
                        }
                        else if($plan_type == 'Plan B'){
                            $planCode = "PABERWB";
                        }
                        else if($plan_type == 'Plan C'){
                            $planCode = "PABERWC";
                        }
                        $additional_cover .= '{
                            "coverCode": "'.$coverCode.'",
                            "planCode": "'.$planCode.'"
                        },';
                    }
                    else if($coverCode == 'PAB3'){
                        $plan_type = $extra_cover->plan_type ?? $extra_cover->sum_insured;
                        if($plan_type == 'Plan A'){
                            $planCode = "PAB3A";
                        }
                        else if($plan_type == 'Plan B'){
                            $planCode = "PAB3B";
                        }
                        $additional_cover .= '{
                            "coverCode": "'.$coverCode.'",
                            "planCode": "'.$planCode.'"
                        },';
                    }
                    else{
                        $additional_cover .= '{
                            "coverCode": "'.$coverCode.'",
                            "coverSumInsured": "'.$coverSumInsured.'"
                        },';
                    }
                }
                $index++;
            }
        }
        //additional driver
        if(empty($input->input->additional_driver)){
            $driver_input = '"unlimitedDriverInd": false,
            "driverDetails": [],';
        }
        else{
            if(count($input->input->additional_driver) == 1){
                $driver_input = '"unlimitedDriverInd": false,
                "driverDetails": [],';
            }
            else if(count($input->input->additional_driver) == 2){
                $driver_input = '"unlimitedDriverInd": false,
                "driverDetails": [{
                        "fullName": "Policy Holder Driver",
                        "identityNumber":'.$input->input->id_number.'
                    }';
                $index = 1;
                foreach($input->input->additional_driver as $value){
                    $driver_input .= ',{
                        "fullName": "Additional  Driver '.$index .'",
                        "identityNumber":'.$value->id_number.'
                    }';
                }
                $driver_input .= '],';
            }
            else if(count($input->input->additional_driver) >= 3){
                $driver_input = '"unlimitedDriverInd": true,
                "driverDetails": [],';
            } 
        }
        $name = $input->input->name ?? 'Tan Ai Ling';//name is mandotory input
        //check avcode selected or default first variant's avcode
        $avcode = $input->input->vehicle->extra_attribute->AvCode;
        if(empty($avcode)){
            $avcode = $input->vix->extra_attribute->AvCode;        
        }
        //check contractnumber
        $contractNumber = $input->input->vehicle->extra_attribute->contractNumber;
        if(empty($contractNumber)){
            $contractNumber = $input->vix->extra_attribute->contractNumber;
        }
        $text = '{
            "salesChannel": "PTR",
            "partnerId": "HOWDEN",
            "contractNumber": "'.$contractNumber.'",
            "effectiveDate": "'.Carbon::parse($input->vix->inception_date)->format('Y-m-d').'",
            "expirationDate": "'.Carbon::parse($input->vix->expiry_date)->format('Y-m-d').'",
            "additionalCover": [
              '.$additional_cover.'
            ],
            "calculateDiscount": {
              "discountPercentage": 0
            },
            '.$driver_input.'
            "vehicle": {
              "avCode": "'.$avcode.'"
            }
          }';

		$result = $this->cURL("update", "quote", $text);

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
    
    private function getMaritalStatusCode($marital_status) : string
    {
        $code = null;

        switch($marital_status) {
            case 'S': {
                $code = '0';
                break;
            }
            case 'M': {
                $code = '1';
                break;
            }
            case 'D': {
                $code = '2';
                break;
            }
            case 'O': {
                $code = '';
                break;
            }
        }

        return $code;
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
            $domain = $host;
            $function = 'getToken';
            $options['headers'] = [
				'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                'Content-Type' => 'application/x-www-form-urlencoded',
			];
			$options['form_params'] = [
				'grant_type' => 'client_credentials',
			];
            $postfield = $options['form_params'];
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
            $domain = $host;

            $options['body'] = $data;
            $postfield = $options['body'];
        }

        $result = HttpClient::curl($method, $host, $options);

        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $domain,
            'path' => $function,
            'request_header' => json_encode($options['headers']),
            'request' => json_encode($postfield),
        ]);

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
        }

        return (object)$result;
	}
}