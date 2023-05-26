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
use App\Models\EGHLLog;

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
	private string $scopeOfCover;
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 500000;
    private const ADJUSTMENT_RATE_UP = 10;
    private const ADJUSTMENT_RATE_DOWN = 10;
	//C005 for comp plus, B57C for comp prem
    private const EXTRA_COVERAGE_LIST = ['B101','111','112','25','57','72','72A','97A','89','89(a)','B57C','C001','C005'];
    private const CART_AMOUNT_LIST = [50, 100, 200];
    private const CART_DAY_LIST = [7, 14, 21];

	public function __construct(int $insurer_id, string $insurer_name)
    {
        $this->company_id = $insurer_id;
        $this->company_name = $insurer_name;
		$this->scopeOfCover = 'COMP PLUS';

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
		//chceking product id for scope
		if(isset($input->product_id)){
			if($input->product_id == 1){
				$this->scopeOfCover = 'COMP PLUS';
			}
			else if($input->product_id == 2){
				$this->scopeOfCover = 'COMP PREM';
			}
		}

		$vix = $this->Q_GetProductList($input);
        if(!$vix->status && is_string($vix->response)) {
            return $this->abort($vix->response);
        }
		else if(isset($vix->response->errorCode)){
			return $this->abort($vix->response->errorCode .', '. $vix->response->errorMessage);
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
		$vix_variant = $this->Q_GetProductListVariant($get_data);

        if(!$vix_variant->status && is_string($vix_variant->response)) {
            return $this->abort($vix_variant->response);
        }
		else if(isset($vix_variant->response->errorCode)){
			return $this->abort($vix_variant->response->errorCode .', '. $vix_variant->response->errorMessage);
		}

        $inception_date = Carbon::parse($vix_variant->response->inceptionDate)->format('Y-m-d');
        $expiry_date = Carbon::parse($vix_variant->response->expiryDate)->format('Y-m-d');
        
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

		//product
		$scope = false;
		foreach ($vix_variant->response->productList as $product) {
			if($product->scopeOfCover == $this->scopeOfCover) {
				$get_product = $product;
				$scope = true;
			}
		}

        // 2. Check Sum Insured -> market price
        $sum_insured = formatNumber($vix_variant->response->sumInsured, 0);
		$MIN_SUM_INSURED = $get_product->minSumInsured ?? $vix_variant->response->productList[0]->minSumInsured;
		$MAX_SUM_INSURED = $get_product->maxSumInsured ?? $vix_variant->response->productList[0]->maxSumInsured;
        if($sum_insured < $MIN_SUM_INSURED || roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true) > $MAX_SUM_INSURED) {
            return $this->abort(__('api.sum_insured_referred_between', [
                'min_sum_insured' => $MIN_SUM_INSURED,
                'max_sum_insured' => $MAX_SUM_INSURED
            ]), config('setting.response_codes.sum_insured_referred'));
        }
        
        $sum_insured_type = "Agreed Value";
        if ($sum_insured < $MIN_SUM_INSURED || $sum_insured > $MAX_SUM_INSURED) {
            return $this->abort(
                __('api.sum_insured_referred_between', ['min_sum_insured' => $MIN_SUM_INSURED, 'max_sum_insured' => $MAX_SUM_INSURED]),
                config('setting.response_codes.sum_insured_referred')
            );
        }

		if($input->id_type == '1'){
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
		}
		else if($input->id_type == '4'){
			if ($this->isNewBusinessRegistrationNumber($input->id_number)) {
				$new_business_registration_number = $input->id_number;
			} else {
				$business_registration_number = $input->id_number;
			}
		}
		//driver
		$defaultDriver = [];
		if($input->id_type != '4'){
			array_push($defaultDriver, [
				'driverName' => $input->name ?? 'Named Driver',
				'newICNo' => $nric_number ?? '',
				'oldICNo' => '',
				'dateofBirth' => $dob ?? '',
				'gender' => $input->gender,
			]);
		}

		//make
		$get_make = explode (" ", $vix_variant->response->modelDesc);
        return (object) [
            'status' => true,
			'header' => $vix_variant->header,
			'vehicleClass' => $get_product->vehicleClass ?? $vix_variant->response->productList[0]->vehicleClass,
			'isRoadTaxAvail' => $vix_variant->response->isRoadTaxAvail,
			'extraCoverageList' => $get_product->extraCoverageList ?? $vix_variant->response->productList[0]->extraCoverageList,
			'defaultDriver' => $defaultDriver,
            'response' => new VIXNCDResponse([
                'chassis_number' => $vix->response->chassisNo,
                'coverage' => $this->coverage_type($this->scopeOfCover),
                'cover_type' => $this->scopeOfCover,
                'engine_capacity' => $vix_variant->response->capacity,
                'engine_number' => $vix->response->engineNo,
                'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
                'inception_date' => Carbon::parse($inception_date)->format('d M Y'),
                'make' => $get_make[0],
                'manufacture_year' => $vix_variant->response->mfgYear,
                'max_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true, $MAX_SUM_INSURED),
                'min_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_DOWN, false, $MIN_SUM_INSURED),
                'model' => $vix_variant->response->modelDesc,
                'ncd_percentage' => floatval($get_product->ncdPercent ?? $vix_variant->response->productList[0]->ncdPercent),
                'seating_capacity' => 0,
                'sum_insured' => $sum_insured,
                'sum_insured_type' => $get_product->basisofCoverage ?? $vix_variant->response->productList[0]->basisofCoverage,
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
		//chceking product id for scope
		if(isset($input->product_id)){
			if($input->product_id == 1){
				$this->scopeOfCover = 'COMP PLUS';
			}
			else if($input->product_id == 2){
				$this->scopeOfCover = 'COMP PREM';
			}
		}

		if($input->id_type == '1'){
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
		}
		else if($input->id_type == '4'){
			if ($this->isNewBusinessRegistrationNumber($input->id_number)) {
				$new_business_registration_number = $input->id_number;
			} else {
				$business_registration_number = $input->id_number;
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
				"input"=>$input,
				"namedDriversList"=>$vehicle->extra_attribute->amgen_quote_input->namedDriversList ?? '',
				"vehicleAgeLoadPercent"=>'',
				"insuredAgeLoadPercent"=>'',
				"claimsExpLoadPercent"=>'',
				'fullquote' => $full_quote,
				'header'=>$vehicle->extra_attribute->amgen_quote_input->header,
            ];

            $motor_premium = $this->Q_GetQuote($data);

			if (!$motor_premium->status) {
                return $this->abort($motor_premium->response);
            }
			else if(isset($motor_premium->response->errorCode)){
				return $this->abort($motor_premium->response->errorCode .', '. $motor_premium->response->errorMessage);
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
			if($this->scopeOfCover == 'COMP PLUS'){
				$available_benefits = array_filter($available_benefits, function ($benefits) {
					return $benefits != 'B57C';
				});
			}
			else if($this->scopeOfCover == 'COMP PREM'){
				$available_benefits = array_filter($available_benefits, function ($benefits) {
					return $benefits != 'C005';
				});
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
                        $_cart_amount = $cart_list[0]->cart_amount_list[1];

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
                        $_sum_insured_amount = $option_list->values[1];

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
                        $_sum_insured_amount = $option_list->values[1];

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
                            'values' => ['20% Sum Insured', '30% Sum Insured', '40% Sum Insured', '50% Sum Insured'],
                            'any_value' => true,
                            'increment' => null
                        ]);
                        $item->option_list = $option_list;

                        // Default to 20% SI
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
			'fullquote' => $full_quote,
			'header'=>$vehicle->extra_attribute->amgen_quote_input->header,
		];

		$motor_premium = $this->Q_GetQuote($data);

		if (!$motor_premium->status) {
			return $this->abort($motor_premium->response);
		}
		else if(isset($motor_premium->response->errorCode)){
			return $this->abort($motor_premium->response->errorCode .', '. $motor_premium->response->errorMessage);
		}

		//address format
        $address = (!empty($input->unit_no) ? $input->unit_no.',' : ''). (!empty($input->building_name) ? $input->building_name.',' : '') .
		$input->address_one.',' . (!empty($input->address_two) ? $input->address_two.',' : '') . $input->city. ',' . $input->postcode. ',' . $input->state. ',';
		$address_set = str_split($address, 30);

		//get quotationNo
		if(strlen($input->state) > 30){
			$state = substr($input->state, 0, 30);
		}
		$text = (object)[
			'id_type' => $input->id_type,
			'id_number' => $input->id_number,
			"newICNo"=>$nric_number ?? '',
			"vehicleClass"=>$vehicle->extra_attribute->amgen_quote_input->vehicleClass,
			"vehicleNo"=>$input->vehicle_number,
			"brand"=>$this->brand,
			"dob"=>$dob ?? '',
			"clientName"=>$input->name ?? config('app.name'),
			"genderCode"=>$input->gender,
			"maritalStatusCode"=>$input->marital_status,
			"insuredAddress1"=> isset($address_set[0]) ? $address_set[0] : '11 FLOOR AIK HUA',
			"insuredAddress2"=> isset($address_set[1]) ? $address_set[1] : '',
			"insuredAddress3"=> isset($address_set[2]) ? $address_set[2] : '',
			"insuredAddress4"=> isset($address_set[3]) ? $address_set[3] : '',
			"vehicleKeptAddress1"=> isset($address_set[0]) ? $address_set[0] : '11 FLOOR AIK HUA',
			"vehicleKeptAddress2"=> isset($address_set[1]) ? $address_set[1] : '',
			"vehicleKeptAddress3"=> isset($address_set[2]) ? $address_set[2] : '',
			"vehicleKeptAddress4"=> isset($address_set[3]) ? $address_set[3] : '',
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
		else if(isset($add_quote->response->errorCode)){
			return $this->abort($add_quote->response->errorCode .', '. $add_quote->response->errorMessage);
		}

        $new_extracover_list = [];
        if(!empty($motor_premium->response->extraCoverageList)) {
            foreach($input->extra_cover as $extra_cover) {
                foreach($motor_premium->response->extraCoverageList as $extra) {
                    if((string) $extra->extraCoverageCode === $extra_cover->extra_cover_code) {
                        $extra_cover->premium = formatNumber((float) $extra->premium);
                        $total_benefit_amount += (float) $extra->premium;
                        $extra_cover->selected = floatval($extra->premium) == 0;

						if($extra_cover->premium > 0){
                            array_push($new_extracover_list, $extra_cover);
                        }
                    }
                }
            }
        }
        $input->extra_cover = $new_extracover_list;
        $premium_data = $motor_premium->response;
        $response = new PremiumResponse([
            'basic_premium' => formatNumber($premium_data->basicPremium),
            'excess_amount' => formatNumber($premium_data->compulsoryExcess),
            'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
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
            'named_drivers_needed' => false,
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
		//chceking product id for scope
		if(isset($input->product_id)){
			if($input->product_id == 1){
				$this->scopeOfCover = 'COMP PLUS';
			}
			else if($input->product_id == 2){
				$this->scopeOfCover = 'COMP PREM';
			}
		}
		// Get Extra Attribute
        $extra_attribute = json_decode($input->insurance->extra_attribute->value);

        // Generate Selected Extra Cover List
        $extra_benefits = [];
		$e_hailing = false;
        foreach ($input->insurance->extra_cover as $extra_cover) {
            array_push($extra_benefits, new ExtraCover([
                'extra_cover_code' => $extra_cover->code,
                'extra_cover_description' => $extra_cover->description,
                'premium' => floatval($extra_cover->amount),
                'sum_insured' => floatval($extra_cover->sum_insured) ?? 0,
                'cart_amount' => (int)$extra_cover->cart_amount ?? 0,
                'cart_day' => (int)$extra_cover->cart_day,
            ]));
			if($extra_cover->code == 'C001'){
				$e_hailing = true;
			}
        }
        $total_payable = formatNumber($input->insurance->premium->total_contribution);

        if (!empty($input->insurance_motor->personal_accident)) {
            $total_payable += formatNumber($input->insurance_motor->personal_accident->total_payable);
        }

        $data = (object) [
            'name' => $input->insurance->holder->name,
            'id_type' => $input->insurance->holder->id_type_id,
            'id_number' => $input->insurance->holder->id_number,
            'gender' => $input->insurance->holder->gender,
            'marital_status' => $input->insurance_motor->marital_status,
            'email' => $input->insurance->holder->email_address ?? $input->email,
            'phone_number' => $input->insurance->holder->phone_number ?? $input->phone_number,
            'unit_no' => '',
            'building_name' => '',
            'address_one' => $input->insurance->address->address_one ?? $input->address_one,
            'address_two' => $input->insurance->address->address_two ?? $input->address_two,
            'city' => $input->insurance->address->city,
            'postcode' => $input->insurance->address->postcode ?? $input->postcode,
            'state' => $input->insurance->address->state ?? $input->state,
            'vehicle_number' => $input->vehicle_number,
			'nvic' => $input->insurance_motor->nvic,
            'vehicle' => (object) [
                'nvic' => $input->insurance_motor->nvic,
                'inception_date' => $input->insurance->inception_date,
                'expiry_date' => $input->insurance->expiry_date,
                'extra_attribute' => (object) [
					'chassis_number' => $extra_attribute->chassis_number,
					'cover_type' => $extra_attribute->cover_type,
					'engine_number' => $extra_attribute->engine_number,
					'seating_capacity' => $extra_attribute->seating_capacity,
				],
                'sum_insured' => formatNumber($input->insurance_motor->market_value),
            ],
            'extra_cover' => $extra_benefits,
            'occupation' => $input->insurance->occupation ?? '',
        ];
		$set_data = (object)[
			'input' => $data,
			"vehicleClass"=>$extra_attribute->amgen_quote_input->vehicleClass,
			"scopeOfCover"=>$data->vehicle->extra_attribute->cover_type,
			"roadTaxOption"=>$extra_attribute->amgen_quote_input->isRoadTaxAvail,
			"vehBodyTypeCode"=>$vehicle->extra_attribute->amgen_quote_input->vehicle_body_code ?? '01',
			"sumInsured"=>$data->vehicle->sum_insured,
			"saveInd"=> 'Y',
			"ptvSelectInd"=>'N',
			"namedDriversList"=>$extra_attribute->amgen_quote_input->namedDriversList,
			"vehicleAgeLoadPercent"=>'',
			"insuredAgeLoadPercent"=>'',
			"claimsExpLoadPercent"=>'',
			'fullquote' => false,
			'header' => $extra_attribute->amgen_quote_input->header,
		];
        $result = $this->Q_GetQuote($set_data);

        if (!$result->status) {
            return $this->abort($result->response . ' (Quotation Number: ' . $extra_attribute->quotation_number . ')', $result->code);
        }

		//get quotationNo
		if(strlen($input->insurance->address->state ?? $input->state) > 30){
			$state = substr($input->insurance->address->state ?? $input->state, 0, 30);
		}
		if($input->insurance->holder->id_type_id == '1'){
			$dobs = str_split($input->insurance->holder->id_number, 2);
			$id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
			$year = intval($dobs[0]);
			if ($year >= 10) {
				$year += 1900;
			} else {
				$year += 2000;
			}
			$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);
			$nric_number = $input->insurance->holder->id_number;
		}
		else if($input->insurance->holder->id_type_id == '4'){
			if ($this->isNewBusinessRegistrationNumber($input->insurance->holder->id_number)) {
				$new_business_registration_number = $input->insurance->holder->id_number;
			} else {
				$business_registration_number = $input->insurance->holder->id_number;
			}
		}
		//address format
		$address = (!empty($input->insurance->address->unit_no ?? '') ? $input->insurance->address->unit_no.',' : ''). (!empty($input->insurance->address->building_name ?? '') ? $input->insurance->address->building_name.',' : '') .
		$input->insurance->address->address_one.',' . (!empty($input->insurance->address->address_two ?? '') ? $input->insurance->address->address_two.',' : '') . $input->insurance->address->city. ',' . $input->insurance->address->postcode. ',' . $input->insurance->address->state. ',';
		$address_set = str_split($address, 30);
		
		$text = (object)[
			'id_type' => $input->insurance->holder->id_type_id,
			'id_number' => $input->insurance->holder->id_number,
			"newICNo"=>$nric_number ?? '',
			"vehicleClass"=>$extra_attribute->amgen_quote_input->vehicleClass,
			"vehicleNo"=>$input->vehicle_number,
			"brand"=>$this->brand,
			"dob"=>$dob ?? '',
			"clientName"=>$input->insurance->holder->name,
			"genderCode"=>$input->insurance->holder->gender,
			"maritalStatusCode"=>$input->insurance_motor->marital_status,
			"insuredAddress1"=> isset($address_set[0]) ? $address_set[0] : '11 FLOOR AIK HUA',
			"insuredAddress2"=> isset($address_set[1]) ? $address_set[1] : '',
			"insuredAddress3"=> isset($address_set[2]) ? $address_set[2] : '',
			"insuredAddress4"=> isset($address_set[3]) ? $address_set[3] : '',
			"vehicleKeptAddress1"=> isset($address_set[0]) ? $address_set[0] : '11 FLOOR AIK HUA',
			"vehicleKeptAddress2"=> isset($address_set[1]) ? $address_set[1] : '',
			"vehicleKeptAddress3"=> isset($address_set[2]) ? $address_set[2] : '',
			"vehicleKeptAddress4"=> isset($address_set[3]) ? $address_set[3] : '',
			"insuredPostCode"=>$input->insurance->address->postcode ?? $input->postcode,
			"vehiclePostCode"=>$input->insurance->address->postcode ?? $input->postcode,
			"mobileNo"=> '0' . ($input->insurance->holder->phone_number ?? $input->phone_number),
			"emailId"=>$input->insurance->holder->email_address ?? $input->email,
			"garagedCode"=>'01',
			"safetyCode"=>'99',
			"newBusRegNo"=> $new_business_registration_number ?? '',
			"busRegNo"=>$business_registration_number ?? '',
			"oldICNo"=> '',
			"qReferenceNo"=>$result->response->qReferenceNo,
			'header'=>$result->header,
		];
		$add_quote = $this->Q_GetAdditionalQuoteInfo($text);
		if (!$add_quote->status) {
			return $this->abort($add_quote->response);
		}
        //getting EGHL log
        // Payment Gateway Charges
        $eghl_log = EGHLLog::where('payment_id', 'LIKE', '%' . $input->insurance_code . '%')
        ->where('txn_status', 0)
        ->latest()
        ->first();

		$cn_data = (object)[
			"quotationNo"=>$add_quote->response->quotationNo,
			"newICNo"=>$nric_number ?? '',
			"oldICNo"=>$nric_number ?? '',
			"busRegNo"=>$business_registration_number ?? '',
			"paymentStatus"=> 'S',
			"paymentMode"=> '',
			"cardNo"=>'',
			"cardHolderName"=>'',
			"paymentAmount"=>$input->payment_amount,
			"payBy"=> 'Total Payable',
			"bankApprovalCode"=>$eghl_log->bank_reference,
			'header'=>$add_quote->header,
		];

		$covernote = $this->GetCovernoteSubmission($cn_data);

		if (!$covernote->status) {
			return $this->abort($covernote->response);
		}

        $response = (object) [
            'policy_number' => $covernote->response->coverNoteNo
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
		//chceking product id for scope
		if(isset($input->product_id)){
			if($input->product_id == 1){
				$this->scopeOfCover = 'COMP PLUS';
			}
			else if($input->product_id == 2){
				$this->scopeOfCover = 'COMP PREM';
			}
		}
		
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
		if($cParams->id_type == '1'){
			$dobs = str_split($cParams->id_number, 2);

			$year = intval($dobs[0]);
			if ($year >= 10) {
				$year += 1900;
			} else {
				$year += 2000;
			}

			$dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);
			$nric_number = $cParams->id_number;
		}
		else if ($cParams->id_type == '4'){
			if ($this->isNewBusinessRegistrationNumber($cParams->id_number)) {
				$new_business_registration_number = $cParams->id_number;
			} else {
				$business_registration_number = $cParams->id_number;
			}
		}

		$text = array(
			"newICNo"=>$nric_number ?? "",
			"oldICNo"=>"",
			"busRegNo"=>$business_registration_number ?? "",
			"vehicleClass"=>"PC",
			"vehicleNo"=>$cParams->vehicle_number,
			"brand"=>$this->brand,
			"insuredPostCode"=>$cParams->postcode,
			"vehiclePostCode"=>$cParams->postcode,
			"dob"=>$dob ?? "",
			"newBusRegNo"=>$new_business_registration_number ?? "",
		);
		
		$encrypted = $this->encrypt(json_encode($text));

		$data = array(
			'requestData' => $encrypted
		);

		$response = $this->cURL("getData","QuickQuotation/GetProductList", json_encode($data), $encrypted);

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
		$response = $this->cURL("with_auth_token","QuickQuotation/GetProductListVariant", json_encode($data), $encrypted,$cParams->header);
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
		$inceptionDate = $cParams->input->vehicle->inception_date;
		$expiryDate = $cParams->input->vehicle->expiry_date;
		//coverage
		$B57C_ext_cvr = [];
		$B57C_exist = false;
		if($cParams->fullquote && $this->scopeOfCover == 'COMP PREM'){
			$get_B57C = [array(
				"extraCoverageCode" => "B57C",
				"inclusionOfPartialCoverSIPercent" => "20",
			)];
			
			$B57C_text = array(
				"vehicleClass"=>$cParams->vehicleClass,
				"scopeOfCover"=>$cParams->scopeOfCover,
				"roadTaxOption"=>$cParams->roadTaxOption,
				"vehBodyTypeCode"=>$cParams->vehBodyTypeCode,
				"sumInsured"=>$cParams->sumInsured,
				"saveInd"=>$cParams->saveInd,
				"ptvSelectInd"=>$cParams->ptvSelectInd,
				"extraCoverageList"=>$get_B57C,
				"namedDriversList"=>$cParams->namedDriversList,
				"vehicleAgeLoadPercent"=>$cParams->vehicleAgeLoadPercent,
				"insuredAgeLoadPercent"=>$cParams->insuredAgeLoadPercent,
				"claimsExpLoadPercent"=>$cParams->claimsExpLoadPercent,
			);

			$B57C_encrypted = $this->encrypt(json_encode($B57C_text));

			$B57C_data = array(
				'requestData' => $B57C_encrypted
			);
			$B57C_response = $this->cURL("with_auth_token","QuickQuotation/GetQuickQuote", json_encode($B57C_data), $B57C_encrypted,$cParams->header);
			if($B57C_response->status){
				$B57C_encrypted = $B57C_response->response->responseData;
				$B57C_decrypted = json_decode($this->decrypt($B57C_response->response->responseData));

				if (empty($B57C_decrypted)) {
					$message = !empty($B57C_response->response) ? $B57C_response->response : __('api.empty_response', ['company' => $this->company_name]);

					return $this->abort($message);
				}
				if(isset($B57C_decrypted->errorMessage)){
					$message = !empty($B57C_decrypted->errorMessage) ? $B57C_decrypted->errorMessage : __('api.empty_response', ['company' => $this->company_name]);

					return $this->abort($message);
				}
				$B57C_ext_cvr = $B57C_decrypted->extraCoverageList;
				$B57C_exist = true;
			}
			else{
				$error = (object)[
					'status'=>$B57C_response->status,
					'response'=>$B57C_response->response,
				];
				return $error;
			}
		}
		//check 57 & B57C, priority 57
		$set_extra_cover = $cParams->input->extra_cover;
        if(array_search('57', array_column($set_extra_cover, 'extra_cover_code')) === false){
            $exist = false;
        }
        else{
            $exist = true;
        }
        if($exist){
            $set_extra_cover = array_filter($set_extra_cover, function ($extra_cover) {
				return $extra_cover->extra_cover_code != 'B57C' && $extra_cover->extra_cover_code != 'B57D' &&
				$extra_cover->extra_cover_code != 'B57E' && $extra_cover->extra_cover_code != 'B57F';
            });
        }
		$extraCoverageList = [];
		foreach($set_extra_cover as $extracover){
			if($extracover->extra_cover_code == '112'){
				if($extracover->cart_day == '7'){
					if($extracover->cart_amount == '50'){
						$extraCoverageSumInsured = 350;
					}
					else if($extracover->cart_amount == '100'){
						$extraCoverageSumInsured = 700;
					}
					else if($extracover->cart_amount == '200'){
						$extraCoverageSumInsured = 1400;
					}
				}
				else if($extracover->cart_day == '14'){
					if($extracover->cart_amount == '50'){
						$extraCoverageSumInsured = 700;
					}
					else if($extracover->cart_amount == '100'){
						$extraCoverageSumInsured = 1400;
					}
					else if($extracover->cart_amount == '200'){
						$extraCoverageSumInsured = 2800;
					}
				}
				else if($extracover->cart_day == '21'){
					if($extracover->cart_amount == '50'){
						$extraCoverageSumInsured = 1050;
					}
					else if($extracover->cart_amount == '100'){
						$extraCoverageSumInsured = 2100;
					}
					else if($extracover->cart_amount == '200'){
						$extraCoverageSumInsured = 4200;
					}
				}
				array_push($extraCoverageList, [
					"extraCoverageCode" => $extracover->extra_cover_code,
					"extraCoverageSumInsured" => $extraCoverageSumInsured,
					"cartAmount" => $extracover->cart_amount,
					"cartDays" => $extracover->cart_day,
				]);
			}
			else if($extracover->extra_cover_code == 'B101'){
				array_push($extraCoverageList, [
					"extraCoverageCode" => $extracover->extra_cover_code,
					"extraCoverageEffectiveDate" => Carbon::parse($inceptionDate)->format('d-m-Y'),
					"extraCoverageExpiryDate" => Carbon::parse($expiryDate)->format('d-m-Y'),
				]);
			}
			else if($extracover->extra_cover_code == 'B57C' || $extracover->extra_cover_code == 'B57D' ||
			$extracover->extra_cover_code == 'B57E' || $extracover->extra_cover_code == 'B57F'){
				if($extracover->plan_type == '20% Sum Insured'){
					$extracover->extra_cover_code == 'B57C';
					array_push($extraCoverageList, [
						"extraCoverageCode" => $extracover->extra_cover_code,
						"inclusionOfPartialCoverSIPercent" => "20",
					]);
				}
				else if($extracover->plan_type == '30% Sum Insured'){
					$extracover->extra_cover_code == 'B57D';
					array_push($extraCoverageList, [
						"extraCoverageCode" => $extracover->extra_cover_code,
						"inclusionOfPartialCoverSIPercent" => "30",
					]);
				}
				else if($extracover->plan_type == '40% Sum Insured'){
					$extracover->extra_cover_code == 'B57E';
					array_push($extraCoverageList, [
						"extraCoverageCode" => $extracover->extra_cover_code,
						"inclusionOfPartialCoverSIPercent" => "40",
					]);
				}
				else if($extracover->plan_type == '50% Sum Insured'){
					$extracover->extra_cover_code == 'B57F';
					array_push($extraCoverageList, [
						"extraCoverageCode" => $extracover->extra_cover_code,
						"inclusionOfPartialCoverSIPercent" => "50",
					]);
				}
			}
			else{
				array_push($extraCoverageList, [
					"extraCoverageCode" => $extracover->extra_cover_code,
					"extraCoverageSumInsured" => $extracover->sum_insured,
				]);
			}
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
		$response = $this->cURL("with_auth_token","QuickQuotation/GetQuickQuote", json_encode($data), $encrypted,$cParams->header);
		if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }
			if($B57C_exist == true){
				array_push($decrypted->extraCoverageList, (object)[
					"extraCoverageCode" => $B57C_ext_cvr[0]->extraCoverageCode,
					"extraCoverageDesc" => $B57C_ext_cvr[0]->extraCoverageDesc,
					"premium" => $B57C_ext_cvr[0]->premium,
				]);
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
		$nationalityCode = (!empty($cParams->id_number) && ($cParams->id_type != '4')) ? $this->getNationalityCode() : '';
		if(empty($cParams->genderCode)){
			$gender = 'M';
		}
		else if($cParams->genderCode == 'O'){
			$gender = 'C';
		}
		else{
			$gender = $cParams->genderCode;
		}
		if($cParams->maritalStatusCode == 'O'){
			$marital_status = 'C';
		}
		else{
			$marital_status = $cParams->maritalStatusCode;
		}
		$text = array(
			"newICNo"=>$cParams->newICNo,
			"oldICNo"=>$cParams->oldICNo,
			"busRegNo"=>$cParams->busRegNo,
			"clientName"=>$cParams->clientName,
			"genderCode"=>$gender,
			"maritalStatusCode"=>$marital_status,
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

		$response = $this->cURL("with_auth_token","QuickQuotation/GetAdditionalQuoteInfo", json_encode($data), $encrypted,$cParams->header);
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
		if($cParams->id_type == '1'){
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
		}
		else if($cParams->id_type == '4'){
			if ($this->isNewBusinessRegistrationNumber($cParams->id_number)) {
				$new_business_registration_number = $cParams->id_number;
			} else {
				$business_registration_number = $cParams->id_number;
			}
		}

		// include occupation code and nationality code
		$occupationCode = !empty($cParams->id_number) ? $this->getOccupationCode() : $this->getOccupationCode('TRADING COMPANY');

		// nationalityCode only Applicable to Individual Clients
		$nationalityCode = (!empty($cParams->id_number) && ($cParams->id_type != '4')) ? $this->getNationalityCode() : '';
		
		if(empty($cParams->gender)){
			$gender = 'M';
		}
		else if($cParams->gender == 'O'){
			$gender = 'C';
		}
		else{
			$gender = $cParams->gender;
		}
		if($cParams->marital_status == 'O'){
			$marital_status = 'C';
		}
		else{
			$marital_status = $cParams->marital_status;
		}
		$text = array(
			"newICNo"=>$nric_number ?? '',
			"oldICNo"=>'',
			"busRegNo"=>$business_registration_number ?? '',
			"vehicleClass"=>'PC',
			"vehicleNo"=>$cParams->vehicle_number,
			"brand"=>$this->brand,
			"dob"=>$dob ?? '',
			"clientName"=>$cParams->name ?? config('app.name'),
			"genderCode"=>$gender,
			"maritalStatusCode"=>$marital_status,
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

		$response = $this->cURL("getData","FullQuotation/GetProductList", json_encode($data), $encrypted);
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

		$response = $this->cURL("with_auth_token","FullQuotation/GetProductListVariant", json_encode($data), $encrypted,$cParams->header);
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));

			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

				$log = APILogs::create([
					'insurance_company_id' => $this->company_id,
					'method' => "Decrypted Response Data",
					'domain' => $this->host.':'.$this->port,
					'path' => "/api/KEC/v1.0/FullQuotation/GetProductList",
					'request_header' => "",
					'request' => "",
					'response_header' => "",
					'response' => json_encode($message),
				]);

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

		$response = $this->cURL("with_auth_token","FullQuotation/GetFullQuote", json_encode($data), $encrypted,$cParams->header);
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

		$response = $this->cURL("getData","Renewal/GetPolicyInfo", json_encode($data), $encrypted);
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

		$response = $this->cURL("with_auth_token","Renewal/GetRenewalQuote", json_encode($data), $encrypted,$cParams->header);
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

		$response = $this->cURL("with_auth_token","GetCovernoteSubmission", json_encode($data), $encrypted,$cParams->header);
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

		$response = $this->cURL("getData","GetQuotationDetails", json_encode($data), $encrypted);
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

		$response = $this->cURL("getData","GetQuotationStatus", json_encode($data), $encrypted);
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

		$response = $this->cURL("getData","GetMasterData", json_encode($data), $encrypted);
        if($response->status){
			$encrypted = $response->response->responseData;
			$decrypted = json_decode($this->decrypt($response->response->responseData));
			
			if (empty($decrypted)) {
                $message = !empty($response->response) ? $response->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

			$this->master_data = $decrypted;
			$response = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $response;
        }
        else{
			$error = (object)[
				'status'=>$response->status,
				'response'=>$response->response,
			];
			return $this->abort($response->response);
        }
	}

	private function getOccupationCode($occupation = 'EXECUTIVE'){
		// check master data
		if (empty($this->master_data)) {
			$check = $this->getMasterData();
			if ($check->status == false) {
				return $this->abort($check->response);
			}
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
			$check = $this->getMasterData();
			if (!$check->status) {
				return $this->abort($check->response);
			}
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

	private function sortExtraCoverList(array $extra_cover_list) : array
    {
        foreach ($extra_cover_list as $_extra_cover) {
            $sequence = 99;
            switch ($_extra_cover->extra_cover_code) {
                case '06': {
                    $sequence = 1;
                    break;
                }
                case '109': { 
                    $sequence = 2;
                    break;
                }
                case '22': { 
                    $sequence = 3;
                    break;
                }
                case '25': { 
                    $sequence = 4;
                    break;
                }
                case '57': { 
                    $sequence = 5;
                    break;
                }
                case '72': { 
                    $sequence = 6;
                    break;
                }
                case '72A': { 
                    $sequence = 7;
                    break;
                }
                case '89': { 
                    $sequence = 8;
                    break;
                }
                case '89(a)': { 
                    $sequence = 9;
                    break;
                }
                case '111': { 
                    $sequence = 10;
                    break;
                }
                case '97A': { 
                    $sequence = 11;
                    break;
                }
                case '112': { 
                    $sequence = 12;
                    break;
                }
                case 'B045': { 
                    $sequence = 13;
                    break;
                }
                case 'B210': { 
                    $sequence = 14;
                    break;
                }
                case 'B101': { 
                    $sequence = 15;
                    break;
                }
                case 'B102': { 
                    $sequence = 16;
                    break;
                }
                case 'B213': { 
                    $sequence = 17;
                    break;
                }
                case 'B57C': { 
                    $sequence = 18;
                    break;
                }
                case 'C001': { 
                    $sequence = 19;
                    break;
                }
                case 'C005': { 
                    $sequence = 20;
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
            case '06': {
				$extra_cover_name = 'TUITION PURPOSE';
				break;
			}
			case '109': { 
				$extra_cover_name = 'EXTENSION OF COVER FOR FERRY TRANSIT TO AND/OR FROM SABAH AND THE FEDERAL TERRITORY OF LABUAN';
				break;
			}
			case '22': { 
				$extra_cover_name = 'CARAVAN TRAILERS (PRIVATE CAR ONLY)';
				break;
			}
			case '25': { 
				$extra_cover_name = 'STRIKE, RIOT AND CIVIL COMMOTION';
				break;
			}
			case '57': { 
				$extra_cover_name = 'INCLUSION OF SPECIAL PERILS / CONVULSIONS OF NATURE';
				break;
			}
			case '72': { 
				$extra_cover_name = 'LEGAL LIABILITY OF PASSENGERS';
				break;
			}
			case '72A': { 
				$extra_cover_name = 'LEGAL LIABILITY TO PASSENGERS';
				break;
			}
			case '89': { 
				$extra_cover_name = 'WINDSCREEN DAMAGE (TEMPERED/LAMINATED GLASS INCLUSIVE LABOUR COST)';
				break;
			}
			case '89(a)': { 
				$extra_cover_name = 'WINDSCREEN DAMAGE (TINTING FILM INCLUSIVE LABOUR COST)';
				break;
			}
			case '111': { 
				$extra_cover_name = 'CURRENT YEAR NCD RELIEF';
				break;
			}
			case '97A': { 
				$extra_cover_name = 'NGV GAS';
				break;
			}
			case '112': { 
				$extra_cover_name = 'COMPENSATION FOR ASSESSED REPAIR TIME (CART)';
				break;
			}
			case 'B045': { 
				$extra_cover_name = 'ADDITIONAL BUSINESS USE';
				break;
			}
			case 'B210': { 
				$extra_cover_name = 'RELIABILITY TRIALS, COMPETITIONS ETC.';
				break;
			}
			case 'B101': { 
				$extra_cover_name = 'THAILAND TRIP';
				break;
			}
			case 'B102': { 
				$extra_cover_name = 'COVER TO WEST KALIMANTAN, INDONESIA';
				break;
			}
			case 'B213': { 
				$extra_cover_name = 'INCREASE OF THIRD PARTY PROPERTY DAMAGE LIABILITY';
				break;
			}
			case 'B57C': { 
				$extra_cover_name = 'INCLUSION OF PARTIAL COVER FOR CONVULSION OF NATURE';
				break;
			}
			case 'C001': { 
				$extra_cover_name = 'PRIVATE HIRE CAR (E-HAILING)';
				break;
			}
			case 'C005': { 
				$extra_cover_name = 'GRAB DRIVER PROTECTOR';
				break;
			}
        }

        return $extra_cover_name;
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

	private function cURL($type = null, $function = null, $data = null, $encrypted_request = null, $additionals = null){
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
                $options['headers']['auth_token'] = toObject($additionals)->auth_token[0];
                $options['headers']['referencedata'] = toObject($additionals)->referenceData[0] ?? toObject($additionals)->referencedata[0];
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
		
		$result = HttpClient::curl('POST', $host, $options);

        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $this->host.':'.$this->port,
            'path' => $path,
            'request_header' => json_encode($options['headers']),
            'request' => json_encode(empty($function) ? $options['form_params'] : $this->decrypt($encrypted_request)),
            'encrypted_request' => json_encode(empty($function) ? NULL : $encrypted_request),
        ]);

        if ($result->status) {
			// Update the API log
			APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => empty($function) ? $result->response : $this->decrypt(json_decode($result->response)->responseData),
                'encrypted_response' => empty($function) ? NULL : json_decode($result->response)->responseData
            ]);

            $json = json_decode($result->response);

            if (empty($json)) {
                $message = !empty($result->response) ? $result->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }
			else if(isset($json->errorCode) && $json->errorCode == "E180"){
				$message = "No renewal more than 2 months.";
				
                return $this->abort($message);
			}
            $result->response = $json;
        } else {
			// Update the API log
			APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => empty($function) ? json_encode($result->response) : (isset(json_decode($result->response)->responseData) ? $this->decrypt(json_decode($result->response)->responseData) : $result->response),
                'encrypted_response' => is_object($result->response) ? json_encode($result->response) : $result->response
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