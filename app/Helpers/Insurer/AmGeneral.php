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

	private string $encrypt_password;
	private string $encrypt_salt;
	private string $encrypt_iv;
	private int $encrypt_pswd_iterations;
	private int $encrypt_key_size;
	private string $brand;
	
	private object $master_data;
	private string $encrypt_method = "AES-256-CBC";
	private string $scopeOfCover = "COMP PREM";
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 500000;
    private const ADJUSTMENT_RATE_UP = 10;
    private const ADJUSTMENT_RATE_DOWN = 10;
    private const EXTRA_COVERAGE_LIST = ['B101','112','25','57','72','72A','97A','89','89(a)','C001'];
    // private const EXTRA_COVERAGE_LIST = ['B101','112','25','57','72','72A','97A','89','89(a)','B57C','C001'];
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
		$this->brand = 'K';
		$this->secret_key = 'HOWDAPI0802!@#';
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
		$vix = $this->F_GetProductList($input);
        if(!$vix->status && is_string($vix->response)) {
            return $this->abort($vix->response);
        }
		if(isset($vix->response->variantSeriesList)){
			$nvicCode = '';
			$variants = [];
			foreach($vix->response->variantSeriesList as $variant){
				if($variant->nvicCode == $input->nvic){
					$nvicCode = $variant->nvicCode;
					array_push($variants, new VariantData([
						'nvic' => $variant->nvicCode,
						'sum_insured' => floatval($variant->marketValue),
						'variant' => $variant->nvicDesc,
					]));
				}
				else if($variant->nvicCode != $input->nvic && $variant->marketValue > 0){
					$nvicCode = $variant->nvicCode;
					array_push($variants, new VariantData([
						'nvic' => $variant->nvicCode,
						'sum_insured' => floatval($variant->marketValue),
						'variant' => $variant->nvicDesc,
					]));
				}
			}
		}
		$get_data = (object)[
			'nvicCode' => $nvicCode,
			'header' => $vix->header,
		];
		$vix_variant = $this->F_GetProductListVariant($get_data);

        $inception_date = Carbon::parse($vix_variant->response->inceptionDate)->format('Y-m-d');
        $expiry_date = Carbon::parse($vix_variant->response->expiryDate)->format('Y-m-d');
        
        $today = Carbon::today()->format('Y-m-d');
        // 1. Check inception date
        if($inception_date < $today) {
            return $this->abort('inception date expired');
        }

        // 2. Check Sum Insured -> market price
        $sum_insured = formatNumber($vix_variant->response->sumInsured, 0);
        if($sum_insured < self::MIN_SUM_INSURED || roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true) > self::MAX_SUM_INSURED) {
            return $this->abort(__('api.sum_insured_referred_between', [
                'min_sum_insured' => self::MIN_SUM_INSURED,
                'max_sum_insured' => self::MAX_SUM_INSURED
            ]), config('setting.response_codes.sum_insured_referred'));
        }
        
        $sum_insured_type = "Agreed Value";
        if ($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(
                __('api.sum_insured_referred_between', ['min_sum_insured' => self::MIN_SUM_INSURED, 'max_sum_insured' => self::MAX_SUM_INSURED]),
                config('setting.response_codes.sum_insured_referred')
            );
        }

		//product
		foreach ($vix_variant->response->productList as $product) {
			if($product->scopeOfCover == $this->scopeOfCover) {
				$get_product = $product;
			}
		}
		switch($input->id_type) {
			case '1': {
				$dobs = str_split($input->id_number, 2);
				$id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
				$year = intval($dobs[0]);
				if ($year >= 10) {
					$year += 1900;
				} else {
					$year += 2000;
				}
				$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);
				$nric_number = $input->id_number;
				break;
			}
			case '6': {
				if ($this->isNewBusinessRegistrationNumber($input->id_number)) {
					$new_business_registration_number = $input->id_number;
				} else {
					$business_registration_number = $input->id_number;
				}

				break;
			}
		}
		//driver
		$defaultDriver = [];
		array_push($defaultDriver, [
			'driverName' => $input->name ?? 'Named Driver',
			'newICNo' => $nric_number,
			'oldICNo' => '',
			'dateofBirth' => $dob,
			'gender' => $input->gender,
		]);

		//make
		$get_make = explode (" ", $vix_variant->response->modelDesc);
        return (object) [
            'status' => true,
			'header' => $vix_variant->header,
			'vehicleClass' => $get_product->vehicleClass,
			'isRoadTaxAvail' => $vix_variant->response->isRoadTaxAvail,
			'extraCoverageList' => $get_product->extraCoverageList,
			'defaultDriver' => $defaultDriver,
            'response' => new VIXNCDResponse([
                'chassis_number' => $vix->response->chassisNo,
                'coverage' => $this->coverage_type($get_product->scopeOfCover),
                'cover_type' => $get_product->scopeOfCover,
                'engine_capacity' => $vix_variant->response->capacity,
                'engine_number' => $vix->response->engineNo,
                'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
                'inception_date' => Carbon::parse($inception_date)->format('d M Y'),
                'make' => $get_make[0],
                'manufacture_year' => $vix_variant->response->mfgYear,
                'max_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true, self::MAX_SUM_INSURED),
                'min_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_DOWN, false, self::MIN_SUM_INSURED),
                'model' => $vix_variant->response->modelDesc,
                'ncd_percentage' => floatval($get_product->ncdPercent),
                'seating_capacity' => 0,
                'sum_insured' => $sum_insured,
                'sum_insured_type' => $get_product->basisofCoverage,
                'variants' => $variants,
                'vehicle_number' => $input->vehicle_number,
                'vehicle_body_code' => null
            ])];
    }

	private function isNewBusinessRegistrationNumber($business_registration_number){
		$regex = '/^\d{12}$/m';

		return (!preg_match($regex, $business_registration_number)) ? false : true;
	}

    private function gendercode(string $gender) : string
    {
		switch ($gender) {
			case 'COMPANY': {
				$code = 'C';

				break;
			}
			case 'FEMALE': {
				$code = 'F';

				break;
			}
			case 'MALE': {
				$code = 'M';

				break;
			}
		}

        return $code;
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

		switch($input->id_type) {
			case '1': {
				$dobs = str_split($input->id_number, 2);
				$id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
				$year = intval($dobs[0]);
				if ($year >= 10) {
					$year += 1900;
				} else {
					$year += 2000;
				}
				$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);
				$nric_number = $input->id_number;
				break;
			}
			case '6': {
				if ($this->isNewBusinessRegistrationNumber($input->id_number)) {
					$new_business_registration_number = $input->id_number;
				} else {
					$business_registration_number = $input->id_number;
				}

				break;
			}
		}

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
					'amgen_quote_input' => (object)[
						'vehicleClass' => $vehicle_vix->vehicleClass,
						'isRoadTaxAvail' => $vehicle_vix->isRoadTaxAvail,
						'vehicle_body_code' => $vehicle_vix->response->vehicle_body_code,
						'extraCoverageList' => $vehicle_vix->extraCoverageList,
						'namedDriversList' => $vehicle_vix->defaultDriver,
						'header' => $vehicle_vix->header,
					],
                ],
            ]);
            // get premium
            $data = (object) [
				"vehicleClass"=>$vehicle->extra_attribute->amgen_quote_input->vehicleClass,
				"scopeOfCover"=>$vehicle->extra_attribute->cover_type,
				"roadTaxOption"=>$vehicle->extra_attribute->amgen_quote_input->isRoadTaxAvail,
				"vehBodyTypeCode"=>$vehicle->extra_attribute->amgen_quote_input->vehicle_body_code ?? '01',
				"sumInsured"=>$vehicle->sum_insured,
				"saveInd"=> 'Y',
				"ptvSelectInd"=>'N',
				// "extraCoverageList"=>$vehicle->extra_attribute->amgen_quote_input->extraCoverageList,
				"namedDriversList"=>$vehicle->extra_attribute->amgen_quote_input->namedDriversList,
				"vehicleAgeLoadPercent"=>'',
				"insuredAgeLoadPercent"=>'',
				"claimsExpLoadPercent"=>'',
				'header'=>$vehicle->extra_attribute->amgen_quote_input->header,
            ];

            $motor_premium = $this->F_GetFullQuote($data);

			if (!$motor_premium->status) {
                return $this->abort($motor_premium->response);
            }

            $basic_premium = formatNumber($motor_premium->response->basicPremium);
            $excess_amount = formatNumber($motor_premium->response->compulsoryExcess);
            $ncd_percentage = $motor_premium->response->ncdPercent;
            $ncd_amount = formatNumber($motor_premium->response->ncdAmount);
            $total_benefit_amount = formatNumber($motor_premium->response->extraCoverageAmount);
            $gross_premium = formatNumber($motor_premium->response->grossPremium);
            $sst_percent = formatNumber($motor_premium->response->sstPercent);
            $sst_amount = formatNumber($motor_premium->response->sstAmount);
            $stamp_duty = formatNumber($motor_premium->response->stampDuty);
            $total_payable = formatNumber($motor_premium->response->totalPayable);
            $net_premium = formatNumber($motor_premium->response->netPremium);

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
					'plan_type' => ''
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
                        $_sum_insured_amount = $option_list->values[0];

                        break;
                    }
					case '89(a)': {
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
                        $_sum_insured_amount = $option_list->values[0];

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
					case 'B57C': {
                        $option_list = new OptionList([
                            'name' => 'sum_insured',
                            'description' => 'Option List',
                            'values' => ['Plan A', 'Plan B', 'Plan C', 'Plan D'],
                            'any_value' => true,
                            'increment' => null
                        ]);
                        $item->option_list = $option_list;

                        // Default to plan A
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
		$data = (object) [
			"vehicleClass"=>$vehicle->extra_attribute->amgen_quote_input->vehicleClass,
			"scopeOfCover"=>$vehicle->extra_attribute->cover_type,
			"roadTaxOption"=>$vehicle->extra_attribute->amgen_quote_input->isRoadTaxAvail,
			"vehBodyTypeCode"=>$vehicle->extra_attribute->amgen_quote_input->vehicle_body_code ?? '01',
			"sumInsured"=>$vehicle->sum_insured,
			"saveInd"=> 'Y',
			"ptvSelectInd"=>'N',
			"input"=>$input,
			"namedDriversList"=>$vehicle->extra_attribute->amgen_quote_input->namedDriversList,
			"vehicleAgeLoadPercent"=>'',
			"insuredAgeLoadPercent"=>'',
			"claimsExpLoadPercent"=>'',
		];

		$motor_premium = $this->Q_GetQuote($data);

		if (!$motor_premium->status) {
			return $this->abort($motor_premium->response);
		}
		//get quotationNo
		$text = (object)[
			'id_number' => $input->id_number,
			"newICNo"=>$nric_number ?? '',
			"vehicleClass"=>$vehicle->extra_attribute->amgen_quote_input->vehicleClass,
			"vehicleNo"=>$input->vehicle_number,
			"brand"=>$this->brand,
			"dob"=>$dob ?? '',
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
			"mobileNo"=>$input->phone_code ?? '0123456789',
			"emailId"=>$input->email,
			"garagedCode"=>'01',
			"safetyCode"=>'99',
			"newBusRegNo"=> $new_business_registration_number ?? '',
			"busRegNo"=>$business_registration_number ?? '',
			"oldICNo"=> '',
			"qReferenceNo"=>$motor_premium->response->qReferenceNo,
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
            'net_premium' => formatNumber($premium_data->netPremium),
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
		$text = array(
			"newICNo"=>$cParams->id_number,
			"oldICNo"=>"",
			"busRegNo"=>"",
			"vehicleClass"=>"PC",
			"vehicleNo"=>$cParams->vehicle_number,
			"brand"=>$this->brand,
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
		$get_vix = $this->Q_GetProductList($cParams->input);
		if(isset($get_vix->response->variantSeriesList)){
			$nvicCode = '';
			foreach($get_vix->response->variantSeriesList as $variant){
				if($variant->nvicCode == $cParams->input->nvic){
					$nvicCode = $variant->nvicCode;
				}
				else if($variant->nvicCode != $cParams->input->nvic && $variant->marketValue > 0){
					$nvicCode = $variant->nvicCode;
				}
			}
		}
		$get_data = (object)[
			'nvicCode' => $nvicCode,
			'header' => $get_vix->header,
		];
		$get_variant = $this->Q_GetProductListVariant($get_data);
		$inceptionDate = $get_variant->response->inceptionDate;
		$expiryDate = $get_variant->response->expiryDate;
		//coverage
		$extraCoverageList = [];
		foreach($cParams->input->extra_cover as $extracover){
			// if($extracover->extra_cover_code == '112'){
			// 	array_push($extraCoverageList, [
			// 		"extraCoverageCode" => $extracover->extra_cover_code,
			// 		"extraCoverageSumInsured" => 350,//$sum_insured,
			// 		"cartAmount" => $extracover->cart_amount,
			// 		"cartDays" => $extracover->cart_day,
			// 	]);
			// }
			
			// if($extracover->extra_cover_code != '112' && $extracover->extra_cover_code != 'B57C' && $extracover->extra_cover_code != 'B101'){
			if($extracover->extra_cover_code == '57'){
				array_push($extraCoverageList, [
					"extraCoverageCode" => $extracover->extra_cover_code,
					"extraCoverageSumInsured" => $extracover->sum_insured,
				]);
			}
			// else if($extracover->extra_cover_code == '112'){
			// 	array_push($extraCoverageList, [
			// 		"extraCoverageCode" => $extracover->extra_cover_code,
			// 		"extraCoverageSumInsured" => 350,//$sum_insured,
			// 		"cartAmount" => $extracover->cart_amount,
			// 		"cartDays" => $extracover->cart_day,
			// 	]);
			// }
			// else if($extracover->extra_cover_code == 'B101'){
			// 	array_push($extraCoverageList, [
			// 		"extraCoverageCode" => $extracover->extra_cover_code,
			// 		"extraCoverageSumInsured" => $extracover->sum_insured,
			// 		"extraCoverageEffectiveDate" => $inceptionDate,
			// 		"extraCoverageExpiryDate" => $expiryDate,
			// 	]);
			// }
			else if($extracover->extra_cover_code == 'B57C' || $extracover->extra_cover_code == 'B57D' ||
			$extracover->extra_cover_code == 'B57E' || $extracover->extra_cover_code == 'B57F'){
				if($extracover->plan_type == 'Plan A'){
					$extracover->extra_cover_code == 'B57C';
					array_push($extraCoverageList, [
						"extraCoverageCode" => $extracover->extra_cover_code,
						"extraCoverageSumInsured" => $extracover->sum_insured,
						"inclusionOfPartialCoverSIPercent" => "20",
					]);
				}
				else if($extracover->plan_type == 'Plan B'){
					$extracover->extra_cover_code == 'B57D';
					array_push($extraCoverageList, [
						"extraCoverageCode" => $extracover->extra_cover_code,
						"extraCoverageSumInsured" => $extracover->sum_insured,
						"inclusionOfPartialCoverSIPercent" => "30",
					]);
				}
				else if($extracover->plan_type == 'Plan C'){
					$extracover->extra_cover_code == 'B57E';
					array_push($extraCoverageList, [
						"extraCoverageCode" => $extracover->extra_cover_code,
						"extraCoverageSumInsured" => $extracover->sum_insured,
						"inclusionOfPartialCoverSIPercent" => "40",
					]);
				}
				else if($extracover->plan_type == 'Plan D'){
					$extracover->extra_cover_code == 'B57F';
					array_push($extraCoverageList, [
						"extraCoverageCode" => $extracover->extra_cover_code,
						"extraCoverageSumInsured" => $extracover->sum_insured,
						"inclusionOfPartialCoverSIPercent" => "50",
					]);
				}
			}
			// else{
			// 	array_push($extraCoverageList, [
			// 		"extraCoverageCode" => $extracover->extra_cover_code,
			// 		"extraCoverageSumInsured" => $extracover->sum_insured,
			// 	]);
			// }
		}
		$text = array(
			"vehicleClass"=>$cParams->vehicleClass,
			"scopeOfCover"=>$cParams->scopeOfCover,
			"roadTaxOption"=>$cParams->roadTaxOption,
			"vehBodyTypeCode"=>$cParams->vehBodyTypeCode,
			"sumInsured"=>$cParams->sumInsured,
			"saveInd"=>$cParams->saveInd,
			"ptvSelectInd"=>$cParams->ptvSelectInd,
			"extraCoverageList"=>$extraCoverageList,
			"namedDriversList"=>$cParams->namedDriversList,
			"vehicleAgeLoadPercent"=>$cParams->vehicleAgeLoadPercent,
			"insuredAgeLoadPercent"=>$cParams->insuredAgeLoadPercent,
			"claimsExpLoadPercent"=>$cParams->claimsExpLoadPercent,
		);

		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);
		$response = $this->cURL("with_auth_token","QuickQuotation/GetQuickQuote", json_encode($data),$get_variant->header);
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
		// include occupation code and nationality code
		$occupationCode = !empty($cParams->id_number) ? $this->getOccupationCode() : $this->getOccupationCode('TRADING COMPANY');

		// nationalityCode only Applicable to Individual Clients
		$nationalityCode = !empty($cParams->id_number) ? $this->getNationalityCode() : '';
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
			"occupationCode"=>$occupationCode,
			"nationalityCode"=>$nationalityCode,
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

	public function F_GetProductList($cParams = null)
	{
		switch($cParams->id_type) {
			case '1': {
				$dobs = str_split($cParams->id_number, 2);
				$id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
				$year = intval($dobs[0]);
				if ($year >= 10) {
					$year += 1900;
				} else {
					$year += 2000;
				}
				$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);
				$nric_number = $cParams->id_number;
				break;
			}
			case '6': {
				if ($this->isNewBusinessRegistrationNumber($cParams->id_number)) {
					$new_business_registration_number = $cParams->id_number;
				} else {
					$business_registration_number = $cParams->id_number;
				}

				break;
			}
		}

		// include occupation code and nationality code
		$occupationCode = !empty($cParams->id_number) ? $this->getOccupationCode() : $this->getOccupationCode('TRADING COMPANY');

		// nationalityCode only Applicable to Individual Clients
		$nationalityCode = !empty($cParams->id_number) ? $this->getNationalityCode() : '';

		$text = array(
			"newICNo"=>$nric_number ?? '',
			"vehicleClass"=>'PC',
			"vehicleNo"=>$cParams->vehicle_number,
			"brand"=>$this->brand,
			"dob"=>$dob ?? '',
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
			"mobileNo"=>$cParams->phone_code ?? '0123456789',
			"emailId"=>$cParams->email,
			"garagedCode"=>'01',
			"safetyCode"=>'99',
			"occupationCode"=>$occupationCode,
			"nationalityCode"=>$nationalityCode,
			"newBusRegNo"=> $new_business_registration_number ?? '',
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
			"nvicCode"=>$cParams->nvicCode,
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
			"extraCoverageList"=>$cParams->extraCoverageList ?? [],
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
			"occupationMaster"=>'Y',
			"nationalityMaster"=>'Y',
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

			$this->master_data = $decrypted;
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $error;
        }
	}

	private function getOccupationCode($occupation = 'EXECUTIVE'){
		// check master data
		if (empty($this->master_data)) {
			$this->getMasterData();
		}

		// return other by default
		$occupation_code = '';

		foreach ($this->master_data->occupationList as $_occupation) {
			if ($_occupation->desc == $occupation) {
				$occupation_code = $_occupation->code;
				break;
			}
		}

		return $occupation_code;
	}

	private function getNationalityCode($id = null){
		// check master data
		if (empty($this->master_data)) {
			$this->getMasterData();
		}

		// return other by default
		$nationality_code = '';

		foreach ($this->master_data->nationalityList as $nationality) {
			if ($nationality->desc == 'Malaysia') {
				$nationality_code = $nationality->code;
				break;
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
		$host = $this->host.':'.$this->port;
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
                'Password' => $this->encrypt($password),
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
        }
		if($function){
			$path = "/api/KEC/v1.0/".$function;
		}
		else{
			$path = "/api/oauth/v2.0/token";
		}
        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $this->host.':'.$this->port,
            'path' => $path,
            'request_header' => json_encode($options['headers']),
            'request' => json_encode($options['body']??$options['form_params']),
        ]);
		
		$result = HttpClient::curl('POST', $host, $options);

        if ($result->status) {
			// Update the API log
			APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => $result->response
            ]);

            $json = json_decode($result->response);

            if (empty($json)) {
                $message = !empty($result->response) ? $result->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }
            $result->response = $json;
        } else {
			// Update the API log
			APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => json_encode($result->response)
            ]);
            $message = !empty($result->response) ? $result->response : __('api.empty_response', ['company' => $this->company_name]);
            if(isset($result->response->status_code)){
                $message = $result->response->status_code;
            }
            return $this->abort($message);
        }
        return (object)$result;
	}
}