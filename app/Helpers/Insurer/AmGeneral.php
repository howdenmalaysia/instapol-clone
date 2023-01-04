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

class AmGeneral implements InsurerLibraryInterface
{
    private string $host;
    private string $agent_code;
    private string $secret_key;
    private string $company_id;
    private string $company_name;
    private string $client_id;
	private string $client_secret;
    private string $client_key;
    private string $token;
    private string $encryption_salt;
    private string $user_id;

	private string $port;
	private string $username;
	private string $password;
	private string $java_loc;
	private string $channel_token;
	private const master_data = '';

	private string $encrypt_password;
	private string $encrypt_salt;
	private string $encrypt_iv;
	private int $encrypt_pswd_iterations;
	private int $encrypt_key_size;
	private string $encrypt_method = "AES-256-CBC";

	private string $CI;

	public function __construct(int $insurer_id, string $insurer_name)
    {
        $this->company_id = $insurer_id;
        $this->company_name = $insurer_name;

		$this->client_id = config('insurer.config.am_config.client_id');
		$this->client_secret = config('insurer.config.am_config.client_secret');
		$this->host = config('insurer.config.am_config.host');
		$this->port = config('insurer.config.am_config.port');
		$this->username = config('insurer.config.am_config.username');
		$this->password = config('insurer.config.am_config.password');
		$this->java_loc = config('insurer.config.am_config.java');

		$this->encrypt_password = config('insurer.config.am_config.encrypt_password');
		$this->encrypt_salt = config('insurer.config.am_config.encrypt_salt');
		$this->encrypt_iv = config('insurer.config.am_config.encrypt_iv');
		$this->encrypt_pswd_iterations = config('insurer.config.am_config.encrypt_pswd_iterations');
		$this->encrypt_key_size = config('insurer.config.am_config.encrypt_key_size');
		$this->channel_token = config('insurer.config.am_config.channel_token');
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

    }
    public function premiumDetails(object $input, $full_quote = false) : object
    {
		dd($this->Q_GetProductList($input));
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
	// public function quotation($qParams, $test = false){
		$text = (object)[
			'vehicleClass' => $qParams->vehicleClass,
			'scopeOfCover' => $qParams->scopeOfCover,
			'roadTaxOption' => 'N',
			'vehBodyTypeCode' => '02',
			'extraCoverageList' => $qParams->extra_coverages,
			'sumInsured' => $qParams->sumInsured,
			'saveInd' => 'Y',
			'namedDriversList' => []
        ];
		$encrypted = $this->encrypt(json_encode($text));
		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("with_auth_token", "QuickQuotation/GetQuickQuote", json_encode($data), (array)$qParams);
		if($response->status){
			$header = $response->header;
			$headers = explode("<br>", nl2br($header,false));
			$auth_token = $referenceData = "";

			foreach ($headers as $item) {
				$items = explode(":", trim($item));

				if(strtolower($items[0]) == "auth_token"){
                    $auth_token = trim($items[1]);
                }
				elseif(strtolower($items[0]) == "referencedata"){
                    $referenceData = trim($items[1]);
                }
			}

			if($test) {
				header('Content-Type: application/json');
				$return['GetQuickQuote'] = [
					'RequestHeader'=>$response->request_header,
					'RequestParams'=>$response->request_param,
					'RequestParamsDecrypted'=>array('requestData' => $text),
					'ResponseHeader'=>$response->header,
					'ResponseBody'=>$response->response,
					'Response_decrypted' => (array)json_decode($this->decrypt($response->response->responseData))
				];
				echo json_encode($return);die;
			}

			if(isset($response->response->responseData)){
				$encrypted = $response->response->responseData;
				$decrypted = (array)json_decode($this->decrypt($encrypted));
				$decrypted['auth_token'] = $auth_token;
				$decrypted['referenceData'] = $referenceData;

				return (object)$decrypted;
            }
			else{
				return (object) [
                    'status' => true,
                    'response' => $response->response,
                ];
            }
        }
		else{
			return (object) [
                'status' => false,
                'response' => '',
            ];
        }
	}

	public function additionalQuoteInfo($params, $test = false){
		// include occupation code and nationality code
		$params['data']['occupationCode'] = !empty($params['data']['newICNo']) ? $this->getOccupationCode() : $this->getOccupationCode('TRADING COMPANY');

		// nationalityCode only Applicable to Individual Clients
		$params['data']['nationalityCode'] = !empty($params['data']['newICNo']) ? $this->getNationalityCode() : null;

		$encrypted = $this->encrypt(json_encode($params['data']));
		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData", "QuickQuotation/GetAdditionalQuoteInfo", json_encode($data));

		if($response->status){
			if(isset($response->response->responseData)){
				$encrypted = $response->response->responseData;
				$decrypted = json_decode($this->decrypt($encrypted));

				$header = $response->header;
				$headers = explode("<br>",nl2br($header,false));

				if($test) {
					header('Content-Type: application/json');
					$return['additionalQuoteInfo'] = [
						'RequestHeader'=>$response->request_header,
						'RequestParams'=>$response->request_param,
						'RequestParamsDecrypted'=>array('requestData' => $params['data']),
						'ResponseHeader'=>$response->header,
						'ResponseBody'=>$response->response,
						'Response_decrypted' => (array)$decrypted
					];
					echo json_encode($return);die;
				}

				$auth_token = $referenceData = "";
				foreach ($headers as $item) {
					$items = explode(":", trim($item));

					if(strtolower($items[0]) == "auth_token"): $auth_token = trim($items[1]);
					elseif(strtolower($items[0]) == "referencedata"): $referenceData = trim($items[1]);
					endif;
				}
				$return = ['auth_token'=> $auth_token, 'referenceData' => $referenceData, 'response' => $decrypted];

				return (object)$return;
            }
			else{
				return $response->response;
            }
        }
		else{
			return false;
        }
	}

	public function GetCovernoteSubmission($params, $test = false){
		$encrypted = $this->encrypt(json_encode($params['data']));
		$data = array(
			'requestData' => $encrypted
		);
		
        //option 1 and 2 got different header selection
		$response = $this->cURL("with_auth_token", "GetCovernoteSubmission", json_encode($data), $params['header']);

		if($response->status){
			if(isset($response->response->responseData)){
				$encrypted = $response->response->responseData;
				$decrypted = json_decode($this->decrypt($encrypted));

				if($test) {
					header('Content-Type: application/json');
					$return['additionalQuoteInfo'] = [
						'RequestHeader'=>$response->request_header,
						'RequestParams'=>$response->request_param,
						'RequestParamsDecrypted'=>array('requestData' => $params['data']),
						'ResponseHeader'=>$response->header,
						'ResponseBody'=>$response->response,
						'Response_decrypted' => (array)$decrypted
					];
					echo json_encode($return);die;
				}
				return $decrypted;
            }
			else{
				$this->abort($token->response);

				return $response->response;
            }
        }
		else{
			return $response;
        }
	}

	public function GetProductListVariant($params){
		$text = '{
			"nvicCode":"'.$params->nvic.'",
		}';
		$encrypted = $this->encrypt(json_encode($text));
		$data = array(
			'requestData' => $encrypted
		);
		dd($params->nvic, $encrypted);
		$result = $this->cURL("with_auth_token", "QuickQuotation/GetProductListVariant", json_encode($data), $params);
		foreach( $result->response as $variant){
			$variantSeriesList = (object)[
				'marketValue' => $variant,
				'modelDesc' => $variant,
				'nvicCode' => $variant,
				'nvicDesc' => $variant,
			];
		}
		$response = (object)[
			"capacity" => $result,
			"chassisNo" => $result,
			"engineNo" => $result,
			"mfgYear" => $result,
			"variantSeriesList" => $variantSeriesList,
		];
		return $response;
	}

	public function Q_GetProductList($cParams = null){
		$dobs = str_split($cParams->id_number, 2);

		$year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}

		$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);

		$text = '{
			"newICNo":"",
			"oldICNo":"",
			"busRegNo":"179811-W",
			"vehicleClass":"PC",
			"vehicleNo":"'.$cParams->vehicle_number.'",
			"brand":"A",
			"insuredPostCode":"'.$cParams->postcode.'",
			"vehiclePostCode":"'.$cParams->postcode.'",
			"dob":"",
			"newBusRegNo":"",
		}';

		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","QuickQuotation/GetProductList",$data);
        dd($response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($encrypted));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function F_GetProductList(){
		$text = array(
			"newICNo" => "750113086211",
			"vehicleClass" => "PC",
			"vehicleNo" => "WSD2363",
			"brand" => "A",
			"dob" => "11-03-1986",
			"clientName" => "Prawira Ari K",
			"genderCode" => "M",
			"maritalStatusCode" => "M",
			"insuredAddress1" => "address 1",
			"insuredAddress2" => "address 2",
			"insuredAddress3" => "",
			"insuredAddress4" => "",
			"vehicleKeptAddress1" => "Address 1",
			"vehicleKeptAddress2" => "Address 2",
			"vehicleKeptAddress3" => "", //Optional
			"vehicleKeptAddress4" => "", //Optional
			"insuredPostCode" => "52100",
			"vehiclePostCode" => "52100",
			"mobileNo" => "0124231749",
			"emailId" => "prawira.ari@gmail.com",
			"garagedCode" => "12",
			"safetyCode" => "99"
		);
		$text = array(
			"newICNo" => "631106107377",
			"vehicleClass" => "PC",
			"vehicleNo" => "WC3737F",
			"brand" => "A",
			"dob" => "06-11-1963",
			"clientName" => "Prawira Ari K",
			"genderCode" => "M",
			"maritalStatusCode" => "M",
			"insuredAddress1" => "address 1",
			"insuredAddress2" => "address 2",
			"insuredAddress3" => "",
			"insuredAddress4" => "",
			"vehicleKeptAddress1" => "Address 1",
			"vehicleKeptAddress2" => "Address 2",
			"vehicleKeptAddress3" => "", //Optional
			"vehicleKeptAddress4" => "", //Optional
			"insuredPostCode" => "52100",
			"vehiclePostCode" => "52100",
			"mobileNo" => "0124231749",
			"emailId" => "prawira.ari@gmail.com",
			"garagedCode" => "12",
			"safetyCode" => "99"
		);

		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","FullQuotation/GetProductList",json_encode($data));

        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($encrypted));

			return $decrypted;
        }
		else{
			return false;
        }
	}

    public function F_GetProductListVariant($params){
        $text = '{
			"nvicCode":"'.$params->nvic.'"
		}';
		$encrypted = $this->encrypt(json_encode($text));
		$data = array(
			'requestData' => $encrypted
		);
		$response = $this->cURL("with_auth_token", "FullQuotation/GetProductListVariant", json_encode($data), $params);
        dd($input,$response, $encrypted);
		if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($encrypted));

			return $decrypted;
        }
		else{
			return false;
        }
    }

    public function full_quotation(object $qParams) : object
    {
	// public function quotation($qParams, $test = false){
		$text = (object)[
			'vehicleClass' => $qParams->vehicleClass,
			'scopeOfCover' => $qParams->scopeOfCover,
			'roadTaxOption' => 'N',
			'vehBodyTypeCode' => '02',
			'extraCoverageList' => $qParams->extra_coverages,
			'sumInsured' => $qParams->sumInsured,
			'saveInd' => 'Y',
			'namedDriversList' => []
        ];
		$encrypted = $this->encrypt(json_encode($text));
		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("with_auth_token", "FullQuotation/GetFullQuote", json_encode($data), (array)$qParams);
		if($response->status){
			$header = $response->header;
			$headers = explode("<br>", nl2br($header,false));
			$auth_token = $referenceData = "";

			foreach ($headers as $item) {
				$items = explode(":", trim($item));

				if(strtolower($items[0]) == "auth_token"){
                    $auth_token = trim($items[1]);
                }
				elseif(strtolower($items[0]) == "referencedata"){
                    $referenceData = trim($items[1]);
                }
			}

			if($test) {
				header('Content-Type: application/json');
				$return['GetQuickQuote'] = [
					'RequestHeader'=>$response->request_header,
					'RequestParams'=>$response->request_param,
					'RequestParamsDecrypted'=>array('requestData' => $text),
					'ResponseHeader'=>$response->header,
					'ResponseBody'=>$response->response,
					'Response_decrypted' => (array)json_decode($this->decrypt($response->response->responseData))
				];
				echo json_encode($return);die;
			}

			if(isset($response->response->responseData)){
				$encrypted = $response->response->responseData;
				$decrypted = (array)json_decode($this->decrypt($encrypted));
				$decrypted['auth_token'] = $auth_token;
				$decrypted['referenceData'] = $referenceData;

				return (object)$decrypted;
            }
			else{
				return (object) [
                    'status' => true,
                    'response' => $response->response,
                ];
            }
        }
		else{
			return (object) [
                'status' => false,
                'response' => '',
            ];
        }
	}
    public function get_motor_policy_info($cParams){
        $text = '{
			"newICNo":"'.$cParams->id_number.'",
			"oldICNo":"",
			"busRegNo":"",
			"policyNo":"",
			"grabConvertCompPlus":"",
			"newBusRegNo":"",
			"vehicleNo":"'.$cParams->vehicle_number.'",
		}';
		// array(
		// 	"newICNo" => "750113086211",
		// 	"oldICNo" => "750113086211",
		// 	"busRegNo" => "WSD2363",
		// 	"policyNo" => "WSD2363",
		// 	"grabConvertCompPlus" => "WSD2363",
		// 	"newBusRegNo" => "WSD2363",
		// 	"vehicleNo" => "WSD2363",
		// );
		$encrypted = $this->encrypt(json_encode($text));
		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData", "Renewal/GetPolicyInfo", json_encode($data));
		dd($response, $encrypted);
		if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($encrypted));

			return $decrypted;
        }
		else{
			return false;
        }
    }

    public function get_motor_renewal_quote($qParams){
        $text = array(
            "insuredAddress1" => "address 1",
			"insuredAddress2" => "address 2",
			"insuredAddress3" => "",
			"insuredAddress4" => "",
			"insuredPostCode" => "52100",
			"occupationCode" => "WSD2363",
			"vehicleKeptAddress1" => "Address 1",
			"vehicleKeptAddress2" => "Address 2",
			"vehicleKeptAddress3" => "", //Optional
			"vehicleKeptAddress4" => "", //Optional
			"vehiclePostCode" => "52100",
			"roadTaxOption" => "0124231749",
			"vehBodyTypeCode" => "prawira.ari@gmail.com",
			"modifyExtraCoverage" => "12",
			"modifyNamedDrivers" => "99",
			"sumInsured" => "750113086211",
			"saveInd" => "750113086211",
			"ptvSelectInd" => "WSD2363",
			"vehicleAgeLoadPercent" => "WSD2363",
			"insuredAgeLoadPercent" => "WSD2363",
			"claimsExpLoadPercent" => "WSD2363",
			"selectedExtraCoverageList" => "WSD2363",
			"selectedNamedDriversList" => "WSD2363",
		);
		$encrypted = $this->encrypt(json_encode($text));
		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("with_auth_token", "Renewal/GetRenewalQuote", json_encode($data), $qParams);
		if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($encrypted));

			return $decrypted;
        }
		else{
			return false;
        }
    }

    public function get_covernote_submission($qParams){
        $text = array(
            "quotationNo" => "address 1",
			"newICNo" => "address 2",
			"oldICNo" => "",
			"busRegNo" => "",
			"paymentStatus" => "52100",
			"paymentMode" => "WSD2363",
			"cardNo" => "Address 1",
			"cardHolderName" => "Address 2",
			"paymentAmount" => "", //Optional
			"payBy" => "", //Optional
			"bankApprovalCode" => "52100",
        );
		$encrypted = $this->encrypt(json_encode($text));
		$data = array(
			'requestData' => $encrypted
		);
        //option 1 and 2 got different header selection
		$response = $this->cURL("with_auth_token", "GetCovernoteSubmission", json_encode($data), $qParams);
		if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($encrypted));

			return $decrypted;
        }
		else{
			return false;
        }
    }

    public function get_quotation_details(){
        $text = array(
			"newICNo" => "address 2",
			"oldICNo" => "",
			"busRegNo" => "",
        );
		$encrypted = $this->encrypt(json_encode($text));
		$data = array(
			'requestData' => $encrypted
		);

        $response = $this->cURL("getData", "GetQuotationDetails", json_encode($data));
		if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($encrypted));

			return $decrypted;
        }
		else{
			return false;
        }
    }

    public function get_quotation_status(){
        $text = array(
			"quotationNo" => "address 2",
        );
		$encrypted = $this->encrypt(json_encode($text));
		$data = array(
			'requestData' => $encrypted
		);

        $response = $this->cURL("getData", "GetQuotationStatus", json_encode($data));
		if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($encrypted));

			return $decrypted;
        }
		else{
			return false;
        }
    }

    public function getMasterData(){
    	$form = [
    		'occupationMaster' => 'Y',
    		'nationalityMaster' => 'Y'
    	];

    	$encrypted = $this->encrypt(json_encode($form));

    	$data = array(
    		'requestData' => $encrypted
    	);
        
    	$response = $this->cURL("getData", "GetMasterData", json_encode($data));

        if($response->status){
    		if(isset($response->response->responseData)){
    			$encrypted = $response->response->responseData;
    			$decrypted = json_decode($this->decrypt($encrypted));

    			$this->master_data = $decrypted;
            }
    		else{
    			return $response->response;
            }
        }
    }

	public function getOccupationCode($occupation = 'EXECUTIVE'){
        // return other by default
        $occupation_code = '';
		// check master data
		if (empty($this->master_data)) {
			$this->getMasterData();
		}
        else{//self add
            foreach ($this->master_data->occupationList as $_occupation) {
                if ($_occupation->desc == $occupation) {
                    $occupation_code = $_occupation->code;
                    break;
                }
            }
        }

		return $occupation_code;
	}

	public function getNationalityCode($id = null){
		// return other by default
		$nationality_code = '';
		// check master data
		if (empty($this->master_data)) {
			$this->getMasterData();
		}
        else{//self add
            foreach ($this->master_data->nationalityList as $nationality) {
                if ($nationality->desc == 'Malaysia') {
                    $nationality_code = $nationality->code;
                    break;
                }
            }
        }


		return $nationality_code;
	}

	private function decrypt($data){
        $first_key = openssl_pbkdf2($this->password, $this->encrypt_salt, $this->encrypt_key_size, $this->encrypt_pswd_iterations, "sha1");
        $mix = base64_decode($data);
        $data = openssl_decrypt($mix,$this->encrypt_method,$first_key,OPENSSL_RAW_DATA,$this->encrypt_iv);
        return $data;
	}

	private function encrypt($data){
        $first_key = openssl_pbkdf2($this->password, $this->encrypt_salt, $this->encrypt_key_size, $this->encrypt_pswd_iterations, "sha1");
        $first_encrypted =openssl_encrypt($data,$this->encrypt_method,$first_key, OPENSSL_RAW_DATA,$this->encrypt_iv);
        $output = base64_encode($first_encrypted);
        return $output;
	}

	private function cURL($type = null, $function = null, $data = null, $additionals = null){
		$port = $this->port;
		$host = $this->host.$port;
		$username = $this->username;
		$password = $this->password;
		$options = [
			'timeout' => 60,
			'http_errors' => false,
			'verify' => false
		];

		if($type == "token"){
			$host .= "/api/oauth/v2.0/token";

            $options['headers'] = [
				'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
				'ClientID' => $this->client_id
			];
			$options['form_params'] = [
				'grant_type' => 'client_credentials',
				'scope' => 'resource.READ,resource.WRITE'
			];

			$postfield = "grant_type=client_credentials&scope=resource.READ,resource.WRITE";
        }
        else{
            $token = $this->get_token();
            $host .= "/api/KEC/v1.0/" . $function;
            $options = [];
            $options['headers'] = [
                'Authorization' => 'Bearer '.(string)$token,
                'Channel-Token' => $this->channel_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Username' => $this->username,
                'Password' => $this->encrypt($this->password),
                'Browser' => 'Chrome',
                'Channel' => 'Kurnia',
                'Device' => 'PC',
            ];

            if ($type == "with_auth_token") {
                $options['headers']['auth_token'] = $additionals->auth_token;
                $options['headers']['referencedata'] = $additionals->referenceData;
            }

            $postfield = $data;
            $options['json'] = $postfield;
			dump($options);
        }

        $result = HttpClient::curl('POST', $host, $options);
        
        if ($result->status) {
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