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
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 500000;
    private const EXTRA_COVERAGE_LIST = ['B101','111','112','25','57','72','72A','89','89(a)','C001','C002'];
    private const CART_AMOUNT_LIST = [50, 100, 200];
    private const CART_DAY_LIST = [7, 14, 21];

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
            'vehicle_number' => $input->vehicle_number,
            'id_number' => $input->id_number,
            'postcode' => $input->postcode,
			'dob' => $dob,
        ];
		
        $vix = $this->Q_GetProductList($data);
        if(!$vix->status && is_string($vix->response)) {
            return $this->abort($vix->response);
        }
		//check nvic
		if(empty($input->nvic)){
			//use first nvic
			$index = 0;
			for($i = 0; $i < count($vix->response->variantSeriesList); $i++){
				if(! $vix->response->variantSeriesList[$i]->marketValue > 0){
					$index++;
				}
			}
			$get_data = (object)[
				'nvicCode' => $vix->response->variantSeriesList[$index]->nvicCode,
				'header' => $vix->header,
			];
			$vix_variant = $this->Q_GetProductListVariant($get_data);
		}
		else{
			$get_data = (object)[
				'nvicCode' => $input->nvic,
				'header' => $vix->header,
			];
			$vix_variant = $this->Q_GetProductListVariant($get_data);
		}
        $inception_date = Carbon::parse($vix_variant->response->inceptionDate)->format('Y-m-d');
        $expiry_date = Carbon::parse($vix_variant->response->expiryDate)->format('Y-m-d');
        
        $today = Carbon::today()->format('Y-m-d');
        // 1. Check inception date
        if($inception_date < $today) {
            return $this->abort('inception date expired');
        }

        // 2. Check Sum Insured -> market price
        $sum_insured = formatNumber($vix_variant->response->sumInsured, 0);
        $sum_insured_type = "Makert Value";
        if ($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(
                __('api.sum_insured_referred_between', ['min_sum_insured' => self::MIN_SUM_INSURED, 'max_sum_insured' => self::MAX_SUM_INSURED]),
                config('setting.response_codes.sum_insured_referred')
            );
        }

        $variants = [];
        foreach($vix->response->variantSeriesList as $_nvic) {
			if($_nvic->marketValue > 0){
				array_push($variants, new VariantData([
				    'nvic' => $_nvic->nvicCode,
				    'sum_insured' => floatval($_nvic->marketValue),
				    'variant' => $_nvic->nvicDesc,
				]));
			}
        }
		//make
		$get_make = explode (" ", $vix_variant->response->modelDesc);
        return (object) [
            'status' => true,
			'header' => $vix_variant->header,
			'vehicleClass' => $vix_variant->response->productList[0]->vehicleClass,
			'isRoadTaxAvail' => $vix_variant->response->isRoadTaxAvail,
			'extraCoverageList' => $vix_variant->response->productList[0]->extraCoverageList,
			'defaultDriver' => $vix_variant->response->productList[0]->defaultDriver,
            'response' => new VIXNCDResponse([
                'chassis_number' => $vix->response->chassisNo,
                'coverage' => $this->coverage_type($vix_variant->response->productList[0]->scopeOfCover),
                'cover_type' => $vix_variant->response->productList[0]->scopeOfCover,
                'engine_capacity' => $vix_variant->response->capacity,
                'engine_number' => $vix->response->engineNo,
                'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
                'inception_date' => Carbon::parse($inception_date)->format('d M Y'),
                'make' => $get_make[0],
                'manufacture_year' => $vix_variant->response->mfgYear,
                'max_sum_insured' => $vix_variant->response->productList[0]->maxSumInsured,
                'min_sum_insured' => $vix_variant->response->productList[0]->minSumInsured,
                'model' => $vix_variant->response->modelDesc,
                'ncd_percentage' => floatval($vix_variant->response->productList[0]->ncdPercent),
                'seating_capacity' => 0,
                'sum_insured' => $sum_insured,
                'sum_insured_type' => $vix_variant->response->productList[0]->basisofCoverage,
                'variants' => $variants,
                'vehicle_number' => $input->vehicle_number,
                'vehicle_body_code' => null
            ])];
    }

    private function coverage_type(string $coverage_type) : string
    {
		switch ($coverage_type) {
			case 'COMP': {
				$description = 'Comprehensive';

				break;
			}
			case 'COMP PLUS': {
				$description = 'Comprehensive Plus';

				break;
			}
			case 'COMP PREM': {
				$description = 'Comprehensive Premier';

				break;
			}
			case 'PTP PLUS': {
				$description = 'Comprehensive PTP Plus';

				break;
			}
			case 'PROTON': {
				$description = 'Comprehensive PIP';

				break;
			}
			case 'TPFT': {
				$description = 'Third Part Fire and Theft';

				break;
			}
			case 'TPFT PREM': {
				$description = 'Third-part Fire and Theft Premier';

				break;
			}
			case 'TP': {
				$description = 'Third party';

				break;
			}
		}

        return $description;
    }

    public function premiumDetails(object $input, $full_quote = false) : object
    {
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
					'vehicleClass' => $vehicle_vix->vehicleClass,
					'isRoadTaxAvail' => $vehicle_vix->isRoadTaxAvail,
					'vehicle_body_code' => $vehicle_vix->response->vehicle_body_code,
					'extraCoverageList' => $vehicle_vix->extraCoverageList,
					'namedDriversList' => $vehicle_vix->defaultDriver,
					'header' => $vehicle_vix->header,
                ],
            ]);

            // get premium
            $data = (object) [
				"vehicleClass"=>$vehicle->extra_attribute->vehicleClass,
				"scopeOfCover"=>$vehicle->extra_attribute->cover_type,
				"roadTaxOption"=>$vehicle->extra_attribute->isRoadTaxAvail,
				"vehBodyTypeCode"=>$vehicle->extra_attribute->vehicle_body_code,
				"sumInsured"=>$vehicle->sum_insured,
				"saveInd"=> 'Y',
				"ptvSelectInd"=>'N',
				"extraCoverageList"=>$vehicle->extra_attribute->extraCoverageList,
				"namedDriversList"=>$vehicle->extra_attribute->namedDriversList,
				"vehicleAgeLoadPercent"=>'',
				"insuredAgeLoadPercent"=>'',
				"claimsExpLoadPercent"=>'',
				'header'=>$vehicle->extra_attribute->header,
            ];

            $motor_premium = $this->Q_GetQuote($data);

			if (!$motor_premium->status) {
                return $this->abort($motor_premium->response);
            }

            $basic_premium = formatNumber($motor_premium->response->basicPremium);
            $excess_amount = 0;
            $ncd_percentage = $motor_premium->response->ncdPercent;
            $ncd_amount = formatNumber($motor_premium->response->ncdAmount);
            $total_benefit_amount = formatNumber($motor_premium->response->extraCoverageAmount);
            $gross_premium = formatNumber($motor_premium->response->grossPremium);
            $sst_percent = formatNumber($motor_premium->response->sstPercent);
            $sst_amount = formatNumber($motor_premium->response->sstAmount);
            $stamp_duty = formatNumber($motor_premium->response->stampDuty);
            $total_payable = formatNumber($motor_premium->response->totalPayable);
            $net_premium = formatNumber($motor_premium->response->netPremiumAfterPtv);

            // Remove Extra Cover which is not entitled
            $available_benefits = self::EXTRA_COVERAGE_LIST;

            $extra_cover_list = [];
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
                    case '112': { // COMPENSATION FOR ASSESSED REPAIR TIME (CART)
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
		$data = (object) [
			"vehicleClass"=>$vehicle->extra_attribute->vehicleClass,
			"scopeOfCover"=>$vehicle->extra_attribute->cover_type,
			"roadTaxOption"=>$vehicle->extra_attribute->isRoadTaxAvail,
			"vehBodyTypeCode"=>$vehicle->extra_attribute->vehicle_body_code,
			"sumInsured"=>$vehicle->sum_insured,
			"saveInd"=> 'Y',
			"ptvSelectInd"=>'N',
			"extraCoverageList"=>$vehicle->extra_attribute->extraCoverageList,
			"namedDriversList"=>$vehicle->extra_attribute->namedDriversList,
			"vehicleAgeLoadPercent"=>'',
			"insuredAgeLoadPercent"=>'',
			"claimsExpLoadPercent"=>'',
			'header'=>$vehicle->extra_attribute->header,
		];

		$motor_premium = $this->Q_GetQuote($data);

		if (!$motor_premium->status) {
			return $this->abort($motor_premium->response);
		}
		//get quotationNo
		
		$text = (object)[
			"newICNo"=>$input->id_number,
			"vehicleClass"=>$vehicle->extra_attribute->vehicleClass,
			"vehicleNo"=>$input->vehicle_number,
			"brand"=>'Kurnia',
			"dob"=>$dob,
			"clientName"=>$input->name ?? config('app.name'),
			"genderCode"=>$input->gender,
			"maritalStatusCode"=>$input->marital_status,
			"insuredAddress1"=>$input->address_one ?? '11 FLOOR AIK HUA',
			"insuredAddress2"=>isset($input->address_two) ? (empty($input->address_two) ? $input->city . ', ' . $input->state : $input->address_two) : '',
			"insuredAddress3"=>isset($input->address_two) ? (empty($input->address_two) ? '' : $input->city . ', ' . $input->state) : '',
			"insuredAddress4"=> '',
			"vehicleKeptAddress1"=>$input->address_one ?? '11 FLOOR AIK HUA',
			"vehicleKeptAddress2"=>isset($input->address_two) ? (empty($input->address_two) ? $input->city . ', ' . $input->state : $input->address_two) : '',
			"vehicleKeptAddress3"=>isset($input->address_two) ? (empty($input->address_two) ? '' : $input->city . ', ' . $input->state) : '',
			"vehicleKeptAddress4"=> '',
			"insuredPostCode"=>$input->postcode,
			"vehiclePostCode"=>$input->postcode,
			"mobileNo"=>$input->phone_code,
			"emailId"=>$input->email,
			"garagedCode"=>'01',
			"safetyCode"=>'01',
			"occupationCode"=>$input->occupation,
			"nationalityCode"=>$input->nationality,
			"newBusRegNo"=>'',
			'header'=>$motor_premium->header,
		];
		$add_quote = $this->Q_GetAdditionalQuoteInfo($text);
		if (!$add_quote->status) {
			return $this->abort($add_quote->response);
		}
        $new_extracover_list = [];
        if(!empty($motor_premium->response->extraCoverageList)) {
            foreach($input->extra_cover as $extra_cover) {
                foreach($motor_premium->response->extraCoverageList as $extra) {
                    if((string) $extra->extraCoverageCode === $extra_cover->extra_cover_code) {
                        $extra_cover->premium = formatNumber((float) $extra->premium);
                        $total_benefit_amount += (float) $extra->premium;
                        $extra_cover->selected = floatval($extra->premium) == 0;

                        array_push($new_extracover_list, $extra_cover);
                    }
                }
            }
        }
        $input->extra_cover = $new_extracover_list;
        $premium_data = $motor_premium->response;
        $response = new PremiumResponse([
            'basic_premium' => formatNumber($premium_data->basicPremium),
            'excess_amount' => formatNumber($premium_data->compulsoryExcess),
            'extra_cover' => $input->extra_cover,
            'gross_premium' => formatNumber($premium_data->grossPremium),
            'ncd_amount' => formatNumber($premium_data->ncdAmount),
            'ncd_percentage' => formatNumber($premium_data->ncdPercent),
            'net_premium' => formatNumber($premium_data->netPremiumAfterPtv),
            'sum_insured' => formatNumber($premium_data->sumInsured ?? 0),
            'min_sum_insured' => formatNumber($premium_data->minSumInsured),
            'max_sum_insured' => formatNumber($premium_data->maxSumInsured),
            'sum_insured_type' => $vehicle->sum_insured_type,
            'sst_amount' => formatNumber($premium_data->sstAmount),
            'sst_percent' => formatNumber($premium_data->sstPercent),
            'stamp_duty' => formatNumber($premium_data->stampDuty),
            'total_benefit_amount' => formatNumber($total_benefit_amount),
            'total_payable' => formatNumber($premium_data->totalPayable),
            'named_drivers_needed' => true,
            'quotation_number' => $add_quote->response->quotationNo,
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

    public function submission(object $input) : object
    {
		// Get Extra Attribute
        $extra_attribute = json_decode($input->insurance->extra_attribute->value);

        // Generate Selected Extra Cover List
        $extra_benefits = [];
        foreach ($input->insurance_motor->extra_cover as $extra_cover) {
            array_push($extra_benefits, (object) [
                'extra_cover_code' => $extra_cover->code,
                'sum_insured' => $extra_cover->sum_insured,
                'cart_day' => $extra_cover->cart_day,
                'cart_amount' => $extra_cover->cart_amount
            ]);
        }

        $total_payable = formatNumber($input->insurance->premium->total_contribution);

        if (!empty($input->insurance_motor->personal_accident)) {
            $total_payable += formatNumber($input->insurance_motor->personal_accident->total_payable);
        }

        $data = (object) [
            'name' => $input->insurance->policy_holder->name,
            'id_type' => $input->insurance->policy_holder->id_type_id,
            'id_number' => $input->insurance->policy_holder->id_number,
            'gender' => $input->insurance->policy_holder->gender,
            'marital_status' => $input->insurance->policy_holder->marital_status,
            'email' => $input->insurance->policy_holder->email,
            'phone_number' => '0' . $input->insurance->policy_holder->phone_number,
            'unit_no' => $input->insurance->address->unit_no,
            'building_name' => $input->insurance->address->building_name,
            'address_one' => $input->insurance->address->address_one,
            'address_two' => $input->insurance->address->address_two,
            'city' => $input->insurance->address->city,
            'postcode' => $input->insurance->address->postcode,
            'state' => $input->insurance->address->state,
            'vehicle_number' => $input->insurance_motor->vehicle_number,
            'vehicle' => (object) [
                'nvic' => $input->insurance_motor->nvic,
                'inception_date' => $input->insurance->inception_date,
                'expiry_date' => $input->insurance->expiry_date,
                'extra_attribute' => $extra_attribute,
                'sum_insured' => $input->insurance_motor->sum_insured
            ],
            'extra_cover' => $extra_benefits,
            'occupation' => $input->insurance->policy_holder->occupation,
        ];

        $result = $this->Q_GetQuote($data);

        if (!$result->status) {
            return $this->abort($result->response . ' (Quotation Number: ' . $extra_attribute->quotation_number . ')', $result->code);
        }

        $response = (object) [
            'policy_number' => $result->response->COVERNOTE_NO
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
			'age' => $input->age,
			'additional_driver' => $input->additional_driver,
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
dump($cParams);
		$text = array(
			"newICNo"=>$cParams->id_number,
			"oldICNo"=>"",
			"busRegNo"=>"",
			"vehicleClass"=>"PC",
			"vehicleNo"=>$cParams->vehicle_number,
			"brand"=>"",
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

        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			dd($decrypted);
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
			return $data;
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
        }
	}

	public function Q_GetProductListVariant($cParams = null)
	{
		$text = array(
			"nvicCode"=>$cParams->nvicCode,
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("with_auth_token","QuickQuotation/GetProductListVariant", json_encode($data),$cParams->header);

		if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
			return $data;
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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

		$response = $this->cURL("with_auth_token","QuickQuotation/GetQuickQuote", json_encode($data),$cParams->header);
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
			return $data;
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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

		$response = $this->cURL("with_auth_token","QuickQuotation/GetAdditionalQuoteInfo", json_encode($data),$cParams->header);
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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
			"newICNo"=>$cParams->id_number,
			"vehicleClass"=>'PC',
			"vehicleNo"=>$cParams->vehicle_number,
			"brand"=>'Kurnia',
			"dob"=>$dob,
			"clientName"=>$cParams->name ?? config('app.name'),
			"genderCode"=>$cParams->gender,
			"maritalStatusCode"=>$cParams->marital_status,
			"insuredAddress1"=>$cParams->address_one ?? '11 FLOOR AIK HUA',
			"insuredAddress2"=>isset($cParams->address_two) ? (empty($cParams->address_two) ? $cParams->city . ', ' . $cParams->state : $cParams->address_two) : '',
			"insuredAddress3"=>isset($cParams->address_two) ? (empty($cParams->address_two) ? '' : $cParams->city . ', ' . $cParams->state) : '',
			"insuredAddress4"=> '',
			"vehicleKeptAddress1"=>$cParams->address_one ?? '11 FLOOR AIK HUA',
			"vehicleKeptAddress2"=>isset($cParams->address_two) ? (empty($cParams->address_two) ? $cParams->city . ', ' . $cParams->state : $cParams->address_two) : '',
			"vehicleKeptAddress3"=>isset($cParams->address_two) ? (empty($cParams->address_two) ? '' : $cParams->city . ', ' . $cParams->state) : '',
			"vehicleKeptAddress4"=> '',
			"insuredPostCode"=>$cParams->postcode,
			"vehiclePostCode"=>$cParams->postcode,
			"mobileNo"=>$cParams->phone_code,
			"emailId"=>$cParams->email,
			"garagedCode"=>'01',
			"safetyCode"=>'01',
			"occupationCode"=>$cParams->occupation,
			"nationalityCode"=>$cParams->nationality,
			"newBusRegNo"=>'',
		);
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","FullQuotation/GetProductList", json_encode($data));
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
			return $data;
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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

		$response = $this->cURL("with_auth_token","FullQuotation/GetProductListVariant", json_encode($data),$cParams->header);
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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

		$response = $this->cURL("with_auth_token","FullQuotation/GetFullQuote", json_encode($data),$cParams->header);
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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

		$response = $this->cURL("with_auth_token","Renewal/GetRenewalQuote", json_encode($data),$cParams->header);
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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

		$response = $this->cURL("with_auth_token","GetCovernoteSubmission", json_encode($data),$cParams->header);
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$data = (object)[
				'status'=>$response->status,
				'response'=>$decrypted,
				'header'=>$response->response_header,
			];
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
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
        $method = 'POST';
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
                $options['headers']['auth_token'] = $additionals['auth_token'][0];
                $options['headers']['referencedata'] = $additionals['referenceData'][0];
            }

            $postfield = $data;
            $options['body'] = $postfield;
			
dump($options);
        }

        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $this->host.$this->port,
            'path' => "/api/KEC/v1.0/".$function,
            'request_header' => json_encode($options['headers']),
            'request' => json_encode($options['body']??$options['form_params']),
        ]);

		$result = HttpClient::curl('POST', $host, $options);

        // Update the API log
        APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => $result->response
            ]);

        if ($result->status) {
            $json = json_decode($result->response);

            if (empty($json)) {
                $message = !empty($result->response) ? $result->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }
            $result->response = $json;
        } else {
            $message = !empty($result->response) ? $result->response : __('api.empty_response', ['company' => $this->company_name]);
            if(isset($result->response->status_code)){
                $message = $result->response->status_code;
            }
            return $this->abort($message);
        }
        return (object)$result;
	}
}