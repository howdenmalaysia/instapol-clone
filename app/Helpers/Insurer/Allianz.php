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
        dd($this->checkUBB($input));
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