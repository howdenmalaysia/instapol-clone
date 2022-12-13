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
		$b = (object)[
			'test'=>1,
		];
        $a = $this->vehicleDetails($b);
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
		// $source_system = $input->source_system;
		// $vehicle_license_id = $input->vehicle_license_id;
		// $identity_type = $input->identity_type;
		// $identity_number = $input->identity_number;
		// $check_ubb_ind = $input->check_ubb_ind;
		// $postal_code = $input->postal_code;
		$text = (object)[
			"sourceSystem" => "PARTNER_ID",
			"vehicleLicenseId" => "AKF2633",
			"identityType" => "NRIC",
			"identityNumber" => "841103011116",
			"checkUbbInd" => 1,
			"postalCode" => "50000",
		];
		$json = json_encode($text);
		$data = array(
			'requestData' => $json
		);
		$response = $this->cURL("getData", "/vehicleDetails", $data);
		dd($response);
    }

    public function premiumDetails(object $input, $full_quote = false) : object
    {

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
            ];

            if ($type == "with_auth_token") {
                $options['headers']['auth_token'] = $additionals['auth_token'];
                $options['headers']['referencedata'] = $additionals['referenceData'];
            }

            $postfield = $data;
            $options['form_params'] = $postfield;
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