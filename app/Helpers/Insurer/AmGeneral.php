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
		$dobs = str_split($input->id_number, 2);

		$year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}

		$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);

		$data = (object) [
            'vehicleNo' => $input->vehicle_number,
            'newICNo' => $input->id_number,
            'insuredPostCode' => $input->postcode,
			'dob' => $dob,
        ];
  
        $vix = $this->getVIXNCD($data);
//         if(!$vix->status && is_string($vix->response)) {
//             return $this->abort($vix->response);
//         }

//         $inception_date = $vix->response->polEffectiveDate;
//         $expiry_date = $vix->response->polExpiryDate;
        
//         $today = Carbon::today()->format('Y-m-d');
//         // 1. Check inception date
//         if($inception_date < $today) {
//             return $this->abort('inception date expired');
//         }

//         // 2. Check Sum Insured -> market price
//         $sum_insured = formatNumber($vix->response->nvicList[0]->vehicleMarketValue, 0);
//         $sum_insured_type = "Makert Value";
//         if ($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
//             return $this->abort(
//                 __('api.sum_insured_referred_between', ['min_sum_insured' => self::MIN_SUM_INSURED, 'max_sum_insured' => self::MAX_SUM_INSURED]),
//                 config('setting.response_codes.sum_insured_referred')
//             );
//         }

//         $nvic = explode('|', (string) $vix->response->nvicList[0]->nvic);
//         //getting model
//         $vehInputModel = (object)[      
//             'makeCode' => $vix->response->makeCode,
//             'modelCode' => $vix->response->modelCode,
//         ];
//         $variants = [];
//         $BodyType = '';
//         $uom = '';
//         $VehModelCode = '';
//         foreach($nvic as $_nvic) {
//             // Get Vehicle Details
//             $details = $this->allianzVariant($vehInputModel);
//             $get_variant = $vix->response->nvicList[0]->vehicleVariant;
//             foreach($details->response->VehicleList as $model_details){
//                 if(str_contains($model_details->Descp, $vix->response->nvicList[0]->vehicleVariant)){
//                     $get_variant = $model_details->Descp;
//                     $uom = $model_details->UOM;
//                     $VehModelCode = $model_details->ModelCode;
//                 }
//             }
//             array_push($variants, new VariantData([
//                 'nvic' => $_nvic,
//                 'sum_insured' => floatval($sum_insured),
//                 'variant' => $get_variant,
//             ]));
//         }
// ////////////////////////////////////////////////////////////////////
//         $vix = $this->getQuotation($data);

//         if (!$vix->status) {
//             return $this->abort($vix->response);
//         }

//         // If failed because no NVIC specified, default to first NVIC returned and try again
//         if($vix->response->RESPONSE_STATUS === 'FAILURE' && Str::contains($vix->response->ERROR[0]->ERROR_DESC, 'MULTIPLE NVIC RECEIVED')) {
//             $data->vehicle->nvic = explode('|', $vix->response->NVIC_CODE)[0];
//             $vix = $this->getQuotation($data);

//             if (!$vix->status) {
//                 return $this->abort($vix->response);
//             }
//         }

//         // Get coverage dates
//         $inception_date = Carbon::createFromFormat('d-m-Y', $vix->response->INCEPTIONDATE);
//         $expiry_date = Carbon::createFromFormat('d-m-Y', $vix->response->CNEXPIRYDATE)->format('Y-m-d');
//         $today = Carbon::today()->format('Y-m-d');

//         // Check Inception Date
//         if ($inception_date < $today) {
//             $gap_in_cover = abs(Carbon::today()->diffInDays($inception_date));
//             if ($gap_in_cover > self::ALLOWED_GAP_IN_COVER) {
//                 return $this->abort(__('api.gap_in_cover', ['days' => $gap_in_cover]), config('setting.response_codes.gap_in_cover'));
//             }
//         } else {
//             // Check 2 Months Before
//             if (Carbon::parse($today)->addMonths(2)->lessThan($inception_date)) {
//                 return $this->abort(__('api.earlier_renewal'), config('setting.response_codes.earlier_renewal'));
//             }
//         }

//         // Check Sum Insured
//         $sum_insured = formatNumber($vix->response->SUM_INSURED, 0);
//         if ($sum_insured < self::MIN_SUM_INSURED || roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true) > self::MAX_SUM_INSURED) {
//             return $this->abort(__('api.sum_insured_referred_between', [
//                 'min_sum_insured' => self::MIN_SUM_INSURED,
//                 'max_sum_insured' => self::MAX_SUM_INSURED
//             ]), config('setting.response_codes.sum_insured_referred'));
//         }

//         $variants = [];
//         array_push($variants, new VariantData([
//             'nvic' => (string) $vix->response->NVIC_CODE,
//             'sum_insured' => floatval($vix->response->SUM_INSURED),
//             'variant' => $this->getModelDetails($vix->response->NVIC_CODE)->variant,
//         ]));

//         return (object) [
//             'status' => true,
//             'response' => new VIXNCDResponse([
//                 'chassis_number' => $vix->response->CHASSIS_NO,
//                 'class_code' => $vix->response->VEHICLE_CLASS,
//                 'coverage' => 'Comprehensive',
//                 'cover_type' => $vix->response->COVERAGE_TYPE,
//                 'engine_capacity' => $vix->response->CAPACITY,
//                 'engine_number' => $vix->response->ENGINE_NUMBER,
//                 'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
//                 'inception_date' => $inception_date->format('d M Y'),
//                 'make' => $vix->response->MAKE_DESC,
//                 'manufacture_year' => $vix->response->YEAR_OF_MANUFACTURING,
//                 'max_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true, self::MAX_SUM_INSURED),
//                 'min_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_DOWN, false, self::MIN_SUM_INSURED),
//                 'model' => str_replace($vix->response->MAKE_DESC . ' ', '', $vix->response->MODEL_DESC),
//                 'ncd_percentage' => floatval($vix->response->NCD_PERCENT),
//                 'seating_capacity' => $vix->response->SEAT,
//                 'sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true, self::MAX_SUM_INSURED),
//                 'sum_insured_type' => $vix->response->ENDT_CLAUSE_CODE === 113 ? 'Market Value' : 'Agreed Value',
//                 'variants' => $variants,
//                 'vehicle_number' => $input->vehicle_number,
//                 'vehicle_body_code' => $vix->response->VEHICLE_BODY
//             ])];
    }

    public function premiumDetails(object $input, $full_quote = false) : object
    {
		dd($this->Q_GetProductList($input));
		$vehicle = $input->vehicle ?? null;
        $ncd_amount = $basic_premium = $total_benefit_amount = $gross_premium = $sst_percent = $sst_amount = $stamp_duty = $excess_amount = $total_payable = 0;
        $pa = null;

        if ($full_quote) {
            $vehicle_vix = $this->vehicleDetails($input);
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
                'make' => $vehicle_vix->response->make,
                'model' => $vehicle_vix->response->model,
                'nvic' => $selected_variant->nvic,
                'variant' => $selected_variant->variant,
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
            $data = (object) [
                'vehicle_number' => $input->vehicle_number,
                'id_type' => $input->id_type,
                'id_number' => $input->id_number,
                'gender' => $input->gender,
                'marital_status' => $input->marital_status,
                'postcode' => $input->postcode,
                'state' => $input->state,
                'region' => $input->region,
                'vehicle' => $vehicle,
                'email' => $input->email,
                'phone_number' => $input->phone_number,
                'nvic' => $vehicle->nvic,
                'postcode' => $input->postcode,
                'occupation' => $input->occupation,
            ];

            $motor_premium = $this->getQuotation($data);

            if (!$motor_premium->status) {
                return $this->abort($motor_premium->response);
            }

            $basic_premium = formatNumber($motor_premium->response->BASIC_PREMIUM);
            $excess_amount = 0;
            $ncd_percentage = $vehicle->ncd_percentage;
            $ncd_amount = formatNumber($motor_premium->response->NCD_AMOUNT);
            $total_benefit_amount = formatNumber($motor_premium->response->EXTRACOVERAGE_AMOUNT);
            $gross_premium = formatNumber($motor_premium->response->GROSS_PREMIUM);
            $sst_percent = formatNumber($motor_premium->response->SST_PERCENTAGE);
            $sst_amount = formatNumber($motor_premium->response->SST_PREMIUM);
            $stamp_duty = formatNumber($motor_premium->response->STAMP_DUTY);
            $total_payable = formatNumber($motor_premium->response->AMT_PAY_CLIENT);
            $net_premium = formatNumber($motor_premium->response->AMT_PAY_CLIENT - $motor_premium->response->COMMISSION);

            // Remove Extra Cover which is not entitled
            $available_benefits = self::EXTRA_COVERAGE_LIST;

            /// 1. Private Hire Car Endorsement (E-Hailing)
            if($input->id_type == config('setting.id_type.company_registration_no')) { //// Company Registered Vehicles
                unset($available_benefits[array_search('EHRP', $available_benefits)]);
            }

            /// 2. NCD Relief
            if($vehicle->ncd_percentage == 0) {
                unset($available_benefits[array_search('111', $available_benefits)]);
            }

            /// 3. Waiver Of Betterment
            /// 4. Unlimited Towing Costs
            $vehicle_age = Carbon::now()->year - $vehicle->manufacture_year;
            if($vehicle_age < 5 || $vehicle_age > 14) {
                unset($available_benefits[array_search('BTWP', $available_benefits)]);

                if($vehicle_age > 15) {
                    unset($available_benefits[array_search('TOWP', $available_benefits)]);
                }
            }

            // Generate Extra Cover List
            foreach($available_benefits as $extra_cover_code) {
                $_sum_insured_amount = $_cart_amount = $_cart_day = 0;

                $item = new ExtraCover([
                    'selected' => false,
                    'readonly' => false,
                    'extra_cover_code' => $extra_cover_code,
                    'extra_cover_description' => '',
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
                    case '97': { // Vehicle Accessories
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
                    case 'LOUP': { // Compesation For Loss Of Use Of Vehicle - E-Ride/Hailing
                        // Generate Options From 1,000 To 10,000
                        $option_list = new OptionList([
                            'name' => 'sum_insured',
                            'description' => 'Sum Insured Amount',
                            'values' => array_diff(generateExtraCoverSumInsured(500, 2000, 500), array(1500)),
                            'any_value' => true,
                            'increment' => 500
                        ]);

                        $item->option_list = $option_list;

                        // Default to RM 500
                        $_sum_insured_amount = $option_list->values[0];

                        break;
                    }
                    case 'PA*P': { // Personal Accident Add-On
                        // Generate Options From 1,000 To 10,000
                        $option_list = new OptionList([
                            'name' => 'sum_insured',
                            'description' => 'Sum Insured Amount',
                            'values' => array_diff(generateExtraCoverSumInsured(25000, 100000, 25000), array(75000)),
                            'any_value' => true,
                            'increment' => 25000
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
		dd($this->vehicleDetails($input));
// 		$options['headers'] = [
// 			// 'Authorization' => 'Bearer '.(string)$token,
// 			// 'Channel-Token' => $this->channel_token,
// 			'Content-Type' => 'application/json',
// 			'Accept' => 'application/json',
// 			'Username' => $this->username,
// 			'Password' => $this->password,
// 			'Browser' => 'Chrome',
// 			'Channel' => 'Kurnia',
// 			'Device' => 'PC',
// 		];
// 		$data = '{"newICNo":"881003086210",
// 		"oldICNo":"",
// 		"busRegNo":"",
// 		"vehicleClass":"PC",
// 		"vehicleNo":"PJD2622",
// 		"brand":"K",
// 		"insuredPostCode":"14000",
// 		"vehiclePostCode":"14000",
// 		"dob":"21-12-1977"}';
// 		$text = array(
// 			"newICNo"=>"881003086210",
// 			"oldICNo"=>"",
// 			"busRegNo"=>"",
// 			"vehicleClass"=>"PC",
// 			"vehicleNo"=>"PJD2622",
// 			"brand"=>"K",
// 			"insuredPostCode"=>"14000",
// 			"vehiclePostCode"=>"14000",
// 			"dob"=>"21-12-1977"
// 		);
// 		dd($this->decrypt('MtFbTbB3e8pi4ZwD3WBfzREBLZNPw+Y0uXBMi7Hfo6VqyTueeaRETj4PLX7PfCQDi19z+SeIQL20eTh0whVwif07+Ur2etapl/S6jo0tcsYzMBFV4owfDmloLUi0OP7DGs/nHfU5mTGXowFDCxksGa+qwgaFDWDXAP5IVeiDnxAgrX8kfLch/J+yfnykNsntynbotRUmdkeUSRE0DU+wGMe+DAzKyJSMu1t6Y2NT8wOY8kz2tRU8ZcNyQ8AZ1ovs'));
		
// 		$encrypted = $this->encrypt($data);
// 		$encrypted1 = $this->encrypt(json_encode($text));
// // dd($encrypted, $encrypted1);
// 		$data = array(
// 			// 'requestData' => 
// 			'requestData' => 'MtFbTbB3e8pi4ZwD3WBfzREBLZNPw+Y0uXBMi7Hfo6VqyTueeaRETj4PLX7PfCQDi19z+SeIQL20eTh0whVwif07+Ur2etapl/S6jo0tcsYzMBFV4owfDmloLUi0OP7DGs/nHfU5mTGXowFDCxksGa+qwgaFDWDXAP5IVeiDnxAgrX8kfLch/J+yfnykNsntynbotRUmdkeUSRE0DU+wGMe+DAzKyJSMu1t6Y2NT8wOY8kz2tRU8ZcNyQ8AZ1ovs'
// 		);
// 		$options['body'] = json_encode($data);
		
// 		$result = HttpClient::curl('POST', 'https://sit.kurnia.com/amgapi/services/enquiry/vix', $options);
// 		dd($result,$options);
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
	
    public function quotation(object $input) : object
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

	public function Q_GetProductList($cParams = null)
	{
		$dobs = str_split($cParams->id_number, 2);

		$year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}

		$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);

		$text = array(
			"newICNo"=>$cParams->id_number,
			"oldICNo"=>"",
			"busRegNo"=>"",
			"vehicleClass"=>"PC",
			"vehicleNo"=>$cParams->vehicle_number,
			"brand"=>"A",
			"insuredPostCode"=>$cParams->postcode,
			"vehiclePostCode"=>$cParams->postcode,
			"dob"=>$dob,
			"newBusRegNo"=>"",
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","QuickQuotation/GetProductList", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function Q_GetProductListVariant($cParams = null)
	{
		$text = array(
			"nvicCode"=>$cParams->nvic,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("with_auth_token","QuickQuotation/GetProductListVariant", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}
	
	public function Q_GetQuote($cParams = null)
	{
		$text = array(
			"vehicleClass"=>$cParams->vehicleClass,
			"scopeOfCover"=>$cParams->scopeOfCover,
			"roadTaxOption"=>$cParams->roadTaxOption,
			"vehBodyTypeCode"=>$cParams->vehBodyTypeCode,
			"sumInsured"=>$cParams->sumInsured,
			"saveInd"=>$cParams->saveInd,
			"ptvSelectInd"=>$cParams->ptvSelectInd,
			"extraCoverageList"=>$cParams->extraCoverageList,
			"namedDriversList"=>$cParams->namedDriversList,
			"vehicleAgeLoadPercent"=>$cParams->vehicleAgeLoadPercent,
			"insuredAgeLoadPercent"=>$cParams->insuredAgeLoadPercent,
			"claimsExpLoadPercent"=>$cParams->claimsExpLoadPercent,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("with_auth_token","QuickQuotation/GetQuickQuote", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function Q_GetAdditionalQuoteInfo($cParams = null)
	{
		$text = array(
			"newICNo"=>$cParams->newICNo,
			"oldICNo"=>$cParams->oldICNo,
			"busRegNo"=>$cParams->busRegNo,
			"clientName"=>$cParams->clientName,
			"genderCode"=>$cParams->genderCode,
			"maritalStatusCode"=>$cParams->maritalStatusCode,
			"insuredAddress1"=>$cParams->insuredAddress1,
			"insuredAddress2"=>$cParams->insuredAddress2,
			"insuredAddress3"=>$cParams->insuredAddress3,
			"insuredAddress4"=>$cParams->insuredAddress4,
			"vehicleKeptAddress1"=>$cParams->vehicleKeptAddress1,
			"vehicleKeptAddress2"=>$cParams->vehicleKeptAddress2,
			"vehicleKeptAddress3"=>$cParams->vehicleKeptAddress3,
			"vehicleKeptAddress4"=>$cParams->vehicleKeptAddress4,
			"mobileNo"=>$cParams->mobileNo,
			"emailId"=>$cParams->emailId,
			"garagedCode"=>$cParams->garagedCode,
			"safetyCode"=>$cParams->safetyCode,
			"qReferenceNo"=>$cParams->qReferenceNo,
			"occupationCode"=>$cParams->occupationCode,
			"nationalityCode"=>$cParams->nationalityCode,
			"newBusRegNo"=>$cParams->newBusRegNo,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","QuickQuotation/GetAdditionalQuoteInfo", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function F_GetProductList($cParams = null)
	{
		$dobs = str_split($cParams->id_number, 2);

		$year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}

		$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);

		$text = array(
			"newICNo"=>$cParams->newICNo,
			"vehicleClass"=>$cParams->vehicleClass,
			"vehicleNo"=>$cParams->vehicleNo,
			"brand"=>$cParams->brand,
			"dob"=>$dob,
			"clientName"=>$cParams->clientName,
			"genderCode"=>$cParams->genderCode,
			"maritalStatusCode"=>$cParams->maritalStatusCode,
			"insuredAddress1"=>$cParams->insuredAddress1,
			"insuredAddress2"=>$cParams->insuredAddress2,
			"insuredAddress3"=>$cParams->insuredAddress3,
			"insuredAddress4"=>$cParams->insuredAddress4,
			"vehicleKeptAddress1"=>$cParams->vehicleKeptAddress1,
			"vehicleKeptAddress2"=>$cParams->vehicleKeptAddress2,
			"vehicleKeptAddress3"=>$cParams->vehicleKeptAddress3,
			"vehicleKeptAddress4"=>$cParams->vehicleKeptAddress4,
			"insuredPostCode"=>$cParams->insuredPostCode,
			"vehiclePostCode"=>$cParams->vehiclePostCode,
			"mobileNo"=>$cParams->mobileNo,
			"emailId"=>$cParams->emailId,
			"garagedCode"=>$cParams->garagedCode,
			"safetyCode"=>$cParams->safetyCode,
			"occupationCode"=>$cParams->occupationCode,
			"nationalityCode"=>$cParams->nationalityCode,
			"newBusRegNo"=>$cParams->newBusRegNo,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","FullQuotation/GetProductList", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function F_GetProductListVariant($cParams = null)
	{
		$text = array(
			"nvicCode"=>$cParams->nvic,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("with_auth_token","FullQuotation/GetProductListVariant", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}
	
	public function F_GetFullQuote($cParams = null)
	{
		$text = array(
			"vehicleClass"=>$cParams->vehicleClass,
			"scopeOfCover"=>$cParams->scopeOfCover,
			"roadTaxOption"=>$cParams->roadTaxOption,
			"vehBodyTypeCode"=>$cParams->vehBodyTypeCode,
			"sumInsured"=>$cParams->sumInsured,
			"saveInd"=>$cParams->saveInd,
			"ptvSelectInd"=>$cParams->ptvSelectInd,
			"vehicleAgeLoadPercent"=>$cParams->vehicleAgeLoadPercent,
			"insuredAgeLoadPercent"=>$cParams->insuredAgeLoadPercent,
			"claimsExpLoadPercent"=>$cParams->claimsExpLoadPercent,
			"extraCoverageList"=>$cParams->extraCoverageList,
			"namedDriversList"=>$cParams->namedDriversList,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("with_auth_token","FullQuotation/GetFullQuote", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function Renewal_GetPolicyInfo($cParams = null)
	{
		$text = array(
			"newICNo"=>$cParams->newICNo,
			"oldICNo"=>$cParams->oldICNo,
			"busRegNo"=>$cParams->busRegNo,
			"policyNo"=>$cParams->policyNo,
			"grabConvertCompPlus"=>$cParams->grabConvertCompPlus,
			"newBusRegNo"=>$cParams->newBusRegNo,
			"vehicleNo"=>$cParams->vehicleNo,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","Renewal/GetPolicyInfo", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function Renewal_GetRenewalQuote($cParams = null)
	{
		$text = array(
			"insuredAddress1"=>$cParams->insuredAddress1,
			"insuredAddress2"=>$cParams->insuredAddress2,
			"insuredAddress3"=>$cParams->insuredAddress3,
			"insuredAddress4"=>$cParams->insuredAddress4,
			"insuredPostCode"=>$cParams->insuredPostCode,
			"occupationCode"=>$cParams->occupationCode,
			"vehicleKeptAddress1"=>$cParams->vehicleKeptAddress1,
			"vehicleKeptAddress2"=>$cParams->vehicleKeptAddress2,
			"vehicleKeptAddress3"=>$cParams->vehicleKeptAddress3,
			"vehicleKeptAddress4"=>$cParams->vehicleKeptAddress4,
			"vehiclePostCode"=>$cParams->vehiclePostCode,
			"roadTaxOption"=>$cParams->roadTaxOption,
			"vehBodyTypeCode"=>$cParams->vehBodyTypeCode,
			"modifyExtraCoverage"=>$cParams->modifyExtraCoverage,
			"modifyNamedDrivers"=>$cParams->modifyNamedDrivers,
			"sumInsured"=>$sumInsured,
			"saveInd"=>$cParams->saveInd,
			"ptvSelectInd"=>$cParams->ptvSelectInd,
			"vehicleAgeLoadPercent"=>$cParams->vehicleAgeLoadPercent,
			"insuredAgeLoadPercent"=>$cParams->insuredAgeLoadPercent,
			"claimsExpLoadPercent"=>$cParams->claimsExpLoadPercent,
			"selectedExtraCoverageList"=>$cParams->selectedExtraCoverageList,
			"selectedNamedDriversList"=>$cParams->selectedNamedDriversList,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("with_auth_token","Renewal/GetRenewalQuote", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function GetCovernoteSubmission($cParams = null)
	{
		$text = array(
			"quotationNo"=>$cParams->quotationNo,
			"newICNo"=>$cParams->newICNo,
			"oldICNo"=>$cParams->oldICNo,
			"busRegNo"=>$cParams->busRegNo,
			"paymentStatus"=>$cParams->paymentStatus,
			"paymentMode"=>$cParams->paymentMode,
			"cardNo"=>$cParams->cardNo,
			"cardHolderName"=>$cParams->cardHolderName,
			"paymentAmount"=>$cParams->paymentAmount,
			"payBy"=>$cParams->payBy,
			"bankApprovalCode"=>$cParams->bankApprovalCode,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","GetCovernoteSubmission", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function GetQuotationDetails($cParams = null)
	{
		$text = array(
			"newICNo"=>$cParams->newICNo,
			"oldICNo"=>$cParams->oldICNo,
			"busRegNo"=>$cParams->busRegNo,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","GetQuotationDetails", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function GetQuotationStatus($cParams = null)
	{
		$text = array(
			"quotationNo"=>$cParams->quotationNo,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","GetQuotationStatus", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
	}

	public function GetMasterData($cParams = null)
	{
		$text = array(
			"occupationMaster"=>$cParams->occupationMaster,
			"nationalityMaster"=>$cParams->nationalityMaster,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","GetMasterData", json_encode($data));
		dd($text, json_encode($text), $data, json_encode($data), $response);
		return $response;
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			return $decrypted;
        }
        else{
			return false;
        }
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
		dump('encrypt', $first_key, $first_encrypted);
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
                'Authorization' => 'Bearer '.$token,
                'Channel-Token' => $this->channel_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Username' => $this->username,
                'Password' => $this->encrypt_password,
                'Browser' => 'Chrome',
                'Channel' => 'Kurnia',
                'Device' => 'PC',
            ];

            if ($type == "with_auth_token") {
                $options['headers']['auth_token'] = $additionals->auth_token;
                $options['headers']['referencedata'] = $additionals->referenceData;
            }

            $postfield = $data;
            $options['body'] = $postfield;
			dump($options);
        }

        $result = HttpClient::curl('POST', $host, $options);
        
        dump($result, $host);
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