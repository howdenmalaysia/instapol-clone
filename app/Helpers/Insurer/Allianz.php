<?php

namespace App\Helpers\Insurer;

use App\DataTransferObjects\Motor\ExtraCover;
use App\DataTransferObjects\Motor\VariantData;
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
    private string $host;
    private string $url_token;
    private string $url;
    private string $token;
    private string $agent_code;
    private string $company_id;
    private string $company_name;

	private string $username;
	private string $password;
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 500000;
    private const OCCUPATION = '99';

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
            'response' => new VIXNCDResponse([
                'body_type_code' => null,
                'body_type_description' => null,
                'chassis_number' => $vix->response->vehicleChassis,
                'coverage' => $vix->response->coverType,
                'engine_capacity' => intval($vix->response->vehicleEngineCC),
                'engine_number' => $vix->response->vehicleEngine,
                'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
                'inception_date' => Carbon::parse($inception_date)->format('d M Y'),
                'make' => $input->vehicle->make ?? '',
                'make_code' => intval($vix->response->makeCode),
                'model' => $input->vehicle->model ?? '',
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
        $avvariant = $this->avVariant($get_avvariant)->response;
        $get_quotation = (object)[
            'input'=>$input,
            'vix'=>$vix,
            'avvariant'=>$avvariant,
        ];
        $quotation = $this->quotation($get_quotation)->response;
        $text = '{
            "ReferenceNo": "'.$quotation->contract->contractNumber.'",
            "ProductCat": "MT",
            "SourceSystem": "PARTNER_ID",
            "ClaimsExp": "0",
            "ReconInd": "N",
            "ExcessWaiveInd": "'.$quotation->contract->excessWaiveInd.'",
            "CheckUbbInd": 1,
            "Policy": {
                "PolicyEffectiveDate": "'.$vix->polEffectiveDate.'",
                "PolicyExpiryDate": "'.$vix->polExpiryDate.'",
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
                        "Capacity": "'.$vix->vehicleEngineCC.'",
                        "MakeCode": "'.$vix->makeCode.'",
                        "Model": "'.$vix->vehicleModel.'",
                        "PiamModel": "28",
                        "Seat": '.$vix->seatingCapacity.',
                        "VehicleNo": "'.$vix->vehicleLicenseId.'",
                        "YearOfManufacture": "'.$vix->yearOfManufacture.'",
                        "NamedDriverList": [{
                            "Age": "'.$input->age.'",
                            "IdentificationNumber": "'.$input->id_number.'"
                        }],
                        "HighPerformanceInd": "'.$quotation->contract->highPerformanceInd.'",
                        "HrtvInd": "'.$quotation->contract->hrtvInd.'"
                    },
                    "CoverList": [{
                        "CoverPremium": {
                            "SumInsured": "'.$vix->nvicList[0]->vehicleMarketValue.'"
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
            dd(123);
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
        $a = (object)[
            'makeCode' => '11',
            'modelCode' => '03',
            'region' => 'W',
            'source_system' => 'PARTNER_ID',
            'vehicle_license_id'=> 'vj8152',
            'identity_type'=> 'NRIC',
            'identity_number'=> '810323145146',
            'check_ubb_ind'=> '1',
            'postal_code'=> '50000',
        ];
        dd($this->update_quotation($input));
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
        $dobs = str_split($qParams->input->id_number, 2);
        $id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
        $year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}
		$dob = strval($year) . "-" . $dobs[1] . "-" . $dobs[2];
        $text = '{
            "partnerId": "PARTNERID",
            "contractNumber": "'.$qParams->vix->contractNumber.'",
            "effectiveDate": "'.$qParams->vix->polEffectiveDate.'",
            "expirationDate": "'.$qParams->vix->polExpiryDate.'",
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
                "sumInsured": "'.$qParams->avvariant->VariantGrp[0]->SumInsured.'",
                "avCode": "'.$qParams->avvariant->VariantGrp[0]->AvCode.'",
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
            "sourceSystem": "PARTNER_ID",
            "vehicleLicenseId": "'.$input->vehicle_number.'",
            "identityType": "'.$this->id_type($input->id_type).'",
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
        dd($postcode_details);
        $get_vehicle_details = (object)[
            'source_system' => "PARTNER_ID",
            'vehicle_license_id' => $input->vehicle_number,
            'identity_type' => "NRIC",
            'identity_number' => $input->id_number,
            'check_ubb_ind' => 1,
            'postal_code' => $postcode_details->Postcode,
        ];
        $vix = $this->vehicleDetails($get_vehicle_details);
        $get_avvariant = (object)[
            'region' => $postcode_details->Region,
            'makeCode' => $vix->vehicleMake,
            'modelCode' => $vix->vehicleModel,
            'makeYear' => $vix->yearOfManufacture,
        ];
        $avvariant = $this->avVariant($get_avvariant)->response;
        dd($vix);
        $text = '{
            "salesChannel": "PTR",
            "partnerId": "AZOL",
            "contractNumber": "CNAZ00000003637",
            "effectiveDate": "YYYY-MM-DD",
            "expirationDate": "YYYY-MM-DD",
            "additionalCover": [
              {
                "coverCode": "72",
                "coverSumInsured": 0
              }
            ],
            "calculateDiscount": {
              "discountPercentage": "5"
            },
            "unlimitedDriverInd": false,
            "driverDetails": [
              {
                "fullName": "LEE KING WEI",
                "identityNumber": "841103011116"
              }
            ],
            "vehicle": {
              "avCode": "HOND93AC"
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