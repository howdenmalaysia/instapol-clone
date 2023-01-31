<?php

namespace App\Helpers\Insurer;

use App\DataTransferObjects\Motor\ExtraCover;
use App\DataTransferObjects\Motor\VariantData;
use App\DataTransferObjects\Motor\Vehicle;
use App\DataTransferObjects\Motor\OptionList;
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

class AIG implements InsurerLibraryInterface
{
    private string $url;
    private string $jpj;
    private string $agent_code;
    private string $password;

    private const SOAP_ACTION_DOMAIN = 'https://gtws2.zurich.com.my/ziapps/zurichinsurance/services';
    private const EXTRA_COVERAGE_LIST = ['25','57','89'];
    private const MIN_SUM_INSURED = 11000;
    private const MAX_SUM_INSURED = 600000;
    private const ADJUSTMENT_RATE_UP = 10;
    private const ADJUSTMENT_RATE_DOWN = 10;
    private const OCCUPATION = '99';
    private const ANTI_THEFT = 'A';
    private const PIAM_DRIVER = '03'; // All Drivers
    private const PURPOSE = 'NB'; // New Business
    private const SAFETY_CODE = 'A'; // ABS & Airbags 2
    private const VEHICLE_CAPACITY_CODE = 'CC';

	public function __construct(int $insurer_id, string $insurer_name)
    {
        $this->company_id = $insurer_id;
        $this->company_name = $insurer_name;
        
		$this->url = config('insurer.config.aig_config.url');
		$this->jpj = config('insurer.config.aig_config.jpj');
		$this->agent_code = config('insurer.config.aig_config.agent_code');
		$this->password = config('insurer.config.aig_config.password');
	}

    private function generateSignature($input) : string
    {
        $text = $input . $this->password;
        $sha_1 = sha1(strval($text));
        $hex = hex2bin($sha_1);
        $hash = base64_encode($hex);

        return $hash;
    }
    
    public function vehicleDetails(object $input) : object
    {
        $data = [
            'id_number' => $input->id_number,
            'vehicle_number' => $input->vehicle_number
        ];
        
        $vix = $this->getVIXNCD($data);
        if(!$vix->status && is_string($vix->response)) {
            return $this->abort($vix->response);
        }
        $get_inception = str_split(str_replace('/','',$vix->response->expirydate), 2);
        $inception_date =  $get_inception[2] . $get_inception[3] . "-" . $get_inception[1] .  "-" . strval(intval($get_inception[0] + 1));
        $get_expiry = str_split(str_replace('/','',$vix->response->expirydate), 2);
        $expiry_date =  $get_expiry[2] . strval(intval($get_inception[3]) + 1) . "-" . $get_expiry[1] .  "-" . $get_expiry[0];
        $today = Carbon::today()->format('Y-m-d');
        // 1. Check inception date
        if($inception_date < $today) {
            return $this->abort('inception date expired');
        }
        $uriSegments = explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if($uriSegments[3] == 'vehicle-details'){
            return $this->abort('Empty variant');
        }
        // 2. Check Sum Insured -> market price
        $sum_insured = formatNumber($vix->response->arrResExtraParam->item[0]->value, 0);
        if($sum_insured < self::MIN_SUM_INSURED || roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true) > self::MAX_SUM_INSURED) {
            return $this->abort(__('api.sum_insured_referred_between', [
                'min_sum_insured' => self::MIN_SUM_INSURED,
                'max_sum_insured' => self::MAX_SUM_INSURED
            ]), config('setting.response_codes.sum_insured_referred'));
        }
        
        $sum_insured_type = "Makert Value";
        if ($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(
                __('api.sum_insured_referred_between', ['min_sum_insured' => self::MIN_SUM_INSURED, 'max_sum_insured' => self::MAX_SUM_INSURED]),
                config('setting.response_codes.sum_insured_referred')
            );
        }

        $nvic = explode('|', (string) $vix->response->NVIC);
        $variants = [];
        $uom = '';// Check Sum Insured
        array_push($variants, new VariantData([
            'nvic' => (string) $vix->response->NVIC,
            'sum_insured' => (double) $vix->response->arrResExtraParam->item[0]->value,
            'variant' => null, //$vix->response->variant,
        ]));
        return (object) [
            'status' => true,
            'usecode' => $vix->response->vehusecode,
            'response' => new VIXNCDResponse([
                'body_type_code' => null,
                'body_type_description' => null,
                'chassis_number' => (string) $vix->response->chassisno,
                'coverage' => 'Comprehensive',
                'engine_capacity' => intval($vix->response->vehcapacity),
                'engine_number' => (string) $vix->response->engineno,
                'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
                'inception_date' => Carbon::parse($inception_date)->format('d M Y'),
                'make' => null,
                'make_code' => intval($vix->response->ismmakecode),
                'model' => null,
                'model_code' => intval($vix->response->ismmodelcode),
                'manufacture_year' => intval($vix->response->makeyear),
                'max_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true, self::MAX_SUM_INSURED),
                'min_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_DOWN, false, self::MIN_SUM_INSURED),
                'sum_insured' => $sum_insured,
                'sum_insured_type' => 'Market Value',
                'ncd_percentage' => floatval($vix->response->ncdperc),
                'seating_capacity' => 0,
                'variants' => $variants,
                'vehicle_number' => (string) $input->vehicle_number,
            ])
        ];
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

    public function cover_note(object $input) : object
    {
        
    }

    public function jpj_status(object $input) : object
    {
        $path = 'GetJPJStatus';
        
        // Generate XML from view
        $xml = view('backend.xml.zurich.jpj_status')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);
        if (!$result->status) {
            return $this->abort($result->response, $result->code);
        }     
        $result_data = $result->response->GetJPJStatusResponse->XmlResult;
        $xml_data = simplexml_load_string($result_data);
        
        return new ResponseData([
            'response' => (object)$response
        ]); 
    }

    public function premiumDetails(object $input, $full_quote = false) : object
    {
        $vehicle = $input->vehicle ?? null;
        $ncd_amount = $basic_premium = $total_benefit_amount = $gross_premium = $sst_percent = $sst_amount = $stamp_duty = $excess_amount = $total_payable = 0;
        $pa = null;
        $dobs = str_split($input->id_number, 2);
        $id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
        $year = intval($dobs[0]);
        if ($year >= 10) {
            $year += 1900;
        } else {
            $year += 2000;
        }
        $dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);
        $region = '';
        if($input->region == 'West'){
            $region = 'W';
        }
        else if($input->region == 'East'){
            $region = 'E';
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
                'make' => $vehicle_vix->response->make ?? $input->vehicle->make,
                'model' => $vehicle_vix->response->model ?? $input->vehicle->model,
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
                    'vehicle_use_code' => (string)$vehicle_vix->usecode,
                    'tariff_premium' => '',
                ],
            ]);
            $data = (object) [
                'input' => $input,
                'vehicle' => $vehicle,
            ];
            $premium = $this->getPremium($data);

            $excess_amount = formatNumber($premium->response->excess);
            $ncd_amount = formatNumber($premium->response->ncd_amount);
            $basic_premium = formatNumber($premium->response->gross_premium + $ncd_amount);
            $total_benefit_amount = 0;
            $gross_premium = formatNumber($premium->response->gross_premium);
            $stamp_duty = formatNumber($premium->response->stamp_duty);
            $sst_amount = formatNumber($premium->response->sst_amount);
            $total_payable = formatNumber($premium->response->gross_due);
            $sst_percent = ($sst_amount / $gross_premium) * 100;
            $vehicle->extra_attribute->tariff_premium = formatNumber($premium->response->tariff_premium);

            $extra_cover_list = [];
            foreach(self::EXTRA_COVERAGE_LIST as $_extra_cover_code) {
                $extra_cover = new ExtraCover([
                    'selected' => false,
                    'readonly' => false,
                    'extra_cover_code' => $_extra_cover_code,
                    'extra_cover_description' => $this->getExtraCoverDescription($_extra_cover_code),
                    'premium' => 0,
                    'sum_insured' => 0
                ]);
                
                $sum_insured_amount = 0;
                
                switch($_extra_cover_code) { 
                    case '25': {
                        $price = ($vehicle_vix->response->sum_insured / 10) * 3;
                        $sum_insured_amount = $price;
                        break;
                    }
                    case '57': {
                        $price = ($vehicle_vix->response->sum_insured / 10) * 3;
                        $sum_insured_amount = $price;
                        break;
                    }
                    case '89': {
                        $extra_cover->extra_cover_name = 'Windscreen or Windows';

                        // Options List for Windscreen
                        $option_list = new OptionList([
                            'name' => 'sum_insured',
                            'description' => 'Sum Insured Amount',
                            'values' => generateExtraCoverSumInsured(500, 10000, 1000),
                            'any_value' => true,
                            'increment' => 100
                        ]);

                        $extra_cover->option_list = $option_list;

                        // Default to RM1,000
                        $sum_insured_amount = $option_list->values[1];

                        break;
                    }
                }

                if(!empty($sum_insured_amount)) {
                    $extra_cover->sum_insured = $sum_insured_amount;
                }

                array_push($extra_cover_list, $extra_cover);
            }
            // Include Extra Covers to Get Premium
            $input->extra_cover = $extra_cover_list;
        }
        $data = (object) [
            'input' => $input,
            'vehicle' => $vehicle,
        ];
        $premium = $this->getPremium($data);

        if (!$premium->status) {
            return $this->abort($premium->response);
        }

        $new_extracover_list = [];
        if(isset($premium->response->extra_benefit->item)) {
            foreach($input->extra_cover as $extra_cover) {
                foreach($premium->response->extra_benefit->item as $item) {
                    if((string) $item->bencode === $extra_cover->extra_cover_code) {
                            $extra_cover->premium = formatNumber($item->benpremium);
                            $total_benefit_amount += floatval($item->benpremium);
                            $extra_cover->selected = floatval($item->benpremium) == 0;

                            if(!empty($extra->sumInsured)) {
                                $extra_cover->sum_insured = formatNumber((float) $item->suminsured);
                            }
                            if($extra_cover->premium > 0){
                                array_push($new_extracover_list, $extra_cover);
                            }
                    }
                }
            }
        }
        $input->extra_cover = $new_extracover_list;
        $premium_data = $premium->response;
        $response = new PremiumResponse([
            'act_premium' => formatNumber($premium_data->act_premium),
            'basic_premium' => formatNumber(($premium_data->gross_premium + $premium_data->ncd_amount) - $total_benefit_amount),
            'excess_amount' => formatNumber($premium_data->excess),
            'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
            'gross_premium' => formatNumber($premium_data->gross_premium),
            'loading' => formatNumber($premium_data->loading_amount),
            'ncd_amount' => formatNumber($premium_data->ncd_amount),
            'ncd_percentage' => formatNumber($premium_data->ncd_percentage),
            'net_premium' => formatNumber($premium_data->net_premium + $premium_data->sst_amount + $premium_data->stamp_duty),
            'sum_insured' => formatNumber($vehicle->sum_insured ?? 0),
            'min_sum_insured' => formatNumber($vehicle->min_sum_insured),
            'max_sum_insured' => formatNumber($vehicle->max_sum_insured),
            'sum_insured_type' => $vehicle->sum_insured_type,
            'sst_amount' => formatNumber($premium_data->sst_amount),
            'sst_percent' => formatNumber($premium_data->sst_percentage),
            'stamp_duty' => formatNumber($premium_data->stamp_duty),
            'tariff_premium' => formatNumber($premium_data->tariff_premium),
            'total_benefit_amount' => formatNumber($total_benefit_amount),
            'total_payable' => formatNumber($premium_data->gross_due),
            'fl_quote_number' => $premium_data->fl_quote_number,
            'named_drivers_needed' => true,
        ]);
        
        if($full_quote) {
            // Revert to premium without extra covers
            $response->excess_amount = $excess_amount;
            $response->basic_premium = $basic_premium;
            $response->ncd = $ncd_amount;
            $response->gross_premium = $gross_premium;
            $response->stamp_duty = $stamp_duty;
            $response->sst_amount = $sst_amount;
            $response->sst_percent = $sst_percent;
            $response->total_benefit_amount = 0;
            $response->total_payable = $total_payable;

            $response->vehicle = $vehicle;
        }
        return (object) [
            'status' => true,
            'response' => $response
        ];
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
            case '97': { 
                $extra_cover_name = 'Vehicle Accessories Endorsement';
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
        // Get Extra Attribute
        $extra_attribute = json_decode($input->insurance->extra_attribute->value);

        switch($input->id_type) {
            case config('setting.id_type.company_registration_no'): {
                $input->company_registration_number = $input->id_number;

                break;
            }
            default: {
                return $this->abort(__('api.unsupported_id_type'), config('setting.response_codes.unsupported_id_types'));
            }
        }

        $input->vehicle = (object) [
            'inception_date' => $input->insurance->inception_date,
            'manufacture_year' => $input->insurance_motor->manufacture_year,
            'ncd_percentage' => $input->insurance_motor->ncd_percentage,
            'nvic' => $input->insurance_motor->nvic,
            'sum_insured' => formatNumber($input->insurance_motor->sum_insured),
            'extra_attribute' => (object) [
                'chassis_number' => $extra_attribute->chassis_number,
                'cover_type' => $extra_attribute->cover_type,
                'engine_number' => $extra_attribute->engine_number,
                'seating_capacity' => $extra_attribute->seating_capacity,
                'request_id' => $extra_attribute->request_id,
            ],
        ];

        // Generate Additional Drivers List
        $additional_driver_list = [];
        foreach($input->insurance_motor->driver as $driver) {
            array_push($additional_driver_list, (object) [
                'age' => getAgeFromIC($driver->id_number),
                'gender' => $this->getGender(getGenderFromIC($driver->id_number)),
                'id_number' => $driver->id_number,
                'name' => $driver->name,
                'relationship' => $driver->relationship_id
            ]);
        }

        // Generate Selected Extra Cover
        $selected_extra_cover = [];
        foreach($input->insurance->extra_cover as $extra_cover) {
            array_push($selected_extra_cover, (object) [
                'code' => $extra_cover->code,
                'description' => $extra_cover->description,
                'premium' => $extra_cover->amount,
                'sum_insured' => $extra_cover->sum_insured ?? 0
            ]);
        }

        $input->additional_driver = $additional_driver_list;
        $input->extra_cover = $selected_extra_cover;

        $premium_result = $this->getPremium($input);

        if(!$premium_result->status) {
            return $this->abort($premium_result->response);
        }

        $input->premium_details = $premium_result;
        $input->vehicle->extra_attributes->request_id = $premium_result->request_id;

        $result = $this->issue_covernote($input);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => (object) [
                'policy_number' => $result->response->policyNo
            ]
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

    private function getPremium(object $input) : ResponseData
    {
        $path = 'GetPremium';
        // $request_id = Str::uuid();
        $request_id = 'D112';
        $effective_time = '00:00:01';
        if(Carbon::parse($input->vehicle->inception_date)->lessThan(Carbon::today())) {
            $effective_time = date('H:i:s', strtotime('now'));
        }

        // Format list additional driver
        if(isset($input->input->additional_driver)) {
            foreach ($input->input->additional_driver as $additional_driver) {
                $additional_driver->age = getAgeFromIC($additional_driver->id_number);
                $additional_driver->date_of_birth = formatDateFromIC($additional_driver->id_number);
                $additional_driver->gender = getGenderFromIC($additional_driver->id_number);
                $additional_driver->driving_exp = $additional_driver->age - 18 < 0 ? 0 : $additional_driver->age - 18;
            }
        }

        // Format Extra Cover Code
        $formatted_extra_cover = [];
        if(isset($input->input->extra_cover)) {
            foreach ($input->input->extra_cover as $extra_cover) {
                $extra_cover_code = $extra_cover->extra_cover_code;
                $extra_cover->extra_cover_description = $this->getExtraBenefitDescription($extra_cover_code);

                if($extra_cover->extra_cover_code == '01') {
                    $extra_cover->sum_insured = 50;
                } else if($extra_cover->extra_cover_code == '02') {
                    $extra_cover->sum_insured = ($input->vehicle->extra_attribute->tariff_premium/100)*25;
                } else if($extra_cover->extra_cover_code == '72') {
                    $extra_cover->sum_insured = 7.50;
                } else if($extra_cover->extra_cover_code == '57' || $extra_cover->extra_cover_code == '25') {
                    $extra_cover->sum_insured = $input->vehicle->sum_insured;
                }

                array_push($formatted_extra_cover, (object) [
                    'extra_cover_code' => $extra_cover_code,
                    'extra_cover_description' => $extra_cover->extra_cover_description,
                    'sum_insured' => $extra_cover->sum_insured ?? 0,
                    'unit' => $extra_cover->unit ?? 0,
                    'premium' => 0,
                    'commperc' => 0,
                    'stampduty' => 0,
                    'name' => '',
                    'newic' => $input->input->id_number,
                ]);
            }
        }

        $body_type = $this->getBodyTypeDetails($input->input->vehicle_body_type ?? '');
        
        $dobs = str_split($input->input->id_number, 2);
        $year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}
		$dob = strval($year) . "-" . $dobs[1] . "-" . $dobs[2];
        $inception_date = Carbon::parse($input->vehicle->inception_date)->format('Y-m-d');
        $expiry_date = Carbon::parse($input->vehicle->expiry_date)->format('Y-m-d');
        if(strtotime($expiry_date)<strtotime($inception_date)){
            $expiry_date = Carbon::parse($expiry_date)->addYear()->format('Y-m-d');
        }
        $item = [];
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->input->nvic,
        ]);
        $data = [
            'address_1' => ($input->input->address_one ?? '11 FLOOR AIK HUA'),
            'address_2' => isset($input->input->address_two) ? (empty($input->input->address_two) ? $input->input->city . ', ' . $input->input->state : $input->input->address_two) : '',
            'address_3' => isset($input->input->address_two) ? (empty($input->input->address_two) ? '' : $input->input->city . ', ' . $input->input->state) : '',
            'agent_code' => $this->agent_code,
            'agtgstregdate' => '',
            'agtgstregno' => '',
            'antitd' => self::ANTI_THEFT,
            'birthdate' => $dob,
            'bizregno' => '',
            'channel' => 'TIB',
            'chassisno' => $input->vehicle->extra_attribute->chassis_number,
            'claimamt' => '0',
            'cncondition' => 'u',
            'commiperc' => 10,
            'compcode' => '71',
            'country' => '',
            'covercode' => '01',
            'discount' => 'NO',
            'discountperc' => 0,
            'driveexp' => getAgeFromIC($input->input->id_number) - 18,
            'effectivedate' => $inception_date,
            'effectivetime' => $effective_time,
            'email' => $input->input->email,
            'engineno' => $input->vehicle->extra_attribute->engine_number,
            'expirydate' => $expiry_date,
            'garage' => 'B',
            'gender' => $input->input->gender,
            'gstclaimperc' => 0,
            'gstcode' => '',
            'gstpurpose' => '',
            'gstreg' => '',
            'gstregdate' => '',
            'gstregdateend' => '',
            'gstregno' => '',
            'hpcode' => '001319',
            'hphoneno' => '',
            'lessor' => '',
            'loadingamt' => '684.45',
            'loadingperc' => '30',
            'makecodemajor' => '33',
            'makecodeminor' => '12',
            'makeyear' => $input->vehicle->manufacture_year,
            'maritalstatus' => $input->input->marital_status,
            'mtcycrider' => 'S',
            'name' => $input->input->name ?? config('app.name'),
            'ncdamt' => 0,
            'ncdperc' => $input->vehicle->ncd_percentage,
            'newic' => $input->input->id_number, //810323-14-5146
            'occupmajor' => '11',
            'oldic' => '',
            'ownershiptype' => $input->input->ownership_type ?? 'I',
            'passportno' => '',
            'piamdrv' => self::PIAM_DRIVER,
            'postcode' => $input->input->postcode,
            'preinscode' => '',
            'preinsname' => '',
            'preinsncd' => 0.00,
            'preinspolno' => '',
            'preinsregno' => 'STG4567',
            'prepoleffdate' => '',
            'prepolexpdate' => '',
            'purchasedate' => '',
            'purchaseprice' => 0,
            'purpose' => self::PURPOSE,
            'quoteno' => '',
            'region' => strtoupper(substr($input->input->region, 0, 1)),
            'regno' => 'STG4567',
            'renewno' => '',
            'reqdatetime' => '',
            'requestid' => 'D112',
            'safety' => self::SAFETY_CODE,
            'seatcapacity' => $input->input->vehicle->extra_attribute->seating_capacity,
            'signature' => $this->generateSignature('D112'),
            'stampduty' => 10,
            'statecode' => '017',
            'suminsured' => $input->vehicle->sum_insured,
            'theftclaim' => 0,
            'thirdclaim' => 0,
            'towndesc' => $this->getStateName($input->input->state),
            'trailerno' => '',
            'usecode' => $input->vehicle->extra_attribute->vehicle_use_code,
            'vehbody' => 'SEDAN',
            'vehbodycode' => 27,
            'vehcapacity' => $input->vehicle->engine_capacity,
            'vehcapacitycode' => self::VEHICLE_CAPACITY_CODE,
            'vehclaim' => 0,
            'vehtypecode' => 'V010010',
            'winclaim' => 0,
            'extra_benefit' => $formatted_extra_cover,
            'add_driver' => $input->input->additional_driver ?? [],
            'item' => $item,
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.premium')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if (!$result->status) {
            return $this->abort($result->response);
        }
        // 1. Check Response Code
        $response_code = (string) $result->response->reqdataReturn->respcode;
        if ($response_code != '1') {
            $message = (string) $result->response->reqdataReturn->respdesc;

            return $this->abort($message);
        }

        // 2. Check Refer Risks
        $refer_code = (string) $result->response->reqdataReturn->refercode;
        if($refer_code != '') {
            $message = (string) $result->response->reqdataReturn->referdesc;

            return $this->abort(__('api.referred_risk', ['company' => $this->company_name, 'reason' => str_replace('^', ', ', $message)]), $refer_code);
        }

        $response = (object) [
            'act_premium' => formatNumber((float) $result->response->reqdataReturn->actprem),
            'commission_amount' => formatNumber((float) $result->response->reqdataReturn->commiamt),
            'excess' => formatNumber((float) $result->response->reqdataReturn->excess),
            'extra_benefit' => $result->response->reqdataReturn->addbendata,
            'fl_quote_number' => (string) $result->response->reqdataReturn->flquoteno,
            'gross_due' => formatNumber((float) $result->response->reqdataReturn->grossdue),
            'gross_due_2' => formatNumber((float) $result->response->reqdataReturn->grossdue2),
            'gross_premium' => formatNumber((float) $result->response->reqdataReturn->grossprem),
            'loading_amount' => formatNumber((float) $result->response->reqdataReturn->loadingamt),
            'loading_percentage' => formatNumber((float) $result->response->reqdataReturn->loadingperc),
            'ncd_amount' => formatNumber((float) $result->response->reqdataReturn->ncdamt),
            'ncd_percentage' => formatNumber((float) $result->response->reqdataReturn->ncdperc),
            'net_due' => formatNumber((float) $result->response->reqdataReturn->netdue),
            'net_due_2' => formatNumber((float) $result->response->reqdataReturn->netdue2),
            'net_premium' => formatNumber((float) $result->response->reqdataReturn->netprem),
            'refer_code' => (string) $result->response->reqdataReturn->refercode,
            'refer_description' => (string) $result->response->reqdataReturn->referdesc,
            'sst_amount' => formatNumber((float) $result->response->reqdataReturn->servicetaxamt),
            'sst_percentage' => formatNumber((int) $result->response->reqdataReturn->servicetaxperc, 0),
            'stamp_duty' => formatNumber((float) $result->response->reqdataReturn->stampduty),
            'tariff_premium' => formatNumber((float) $result->response->reqdataReturn->tariffpremium),
        ];

        return new ResponseData([
            'response' => $response
        ]);
    }

    private function getVIXNCD(array $input) : ResponseData
    {
        $path = 'GetVIXNCD';
        $request_id = Str::uuid();
        $data["agent_code"] = $this->agent_code;
        $data["item"] = [];
        // array_push($data['item'], (object) [
        //     'paramIndicator' => '1',
        //     'paramRemark' => '1',
        //     'paramValue' => '1',
        // ]);
        $data["biz_reg_no"] = '';
        $data["comp_code"] = '71';
        $data["new_ic"] = $input['id_number'];
        $data["old_ic"] = '';
        $data["passport_no"] = '';
        $data["reg_no"] = $input['vehicle_number'];
        $data["request_id"] = 'D112';
        $data["signature"] = $this->generateSignature('D112');

        // Generate XML from view
        $xml = view('backend.xml.aig.getvixncd')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }
        $result_data = $result->response->getVixNcdReqReturn;
        // Check for Error
        $error = '';
        if((string)$result_data->respcode != '1'){
            return $this->abort($result_data->RespDesc);
        }

        return new ResponseData([
            'response' => $result_data,
        ]);
    }

    private function issue_covernote(object $input) : ResponseData
    {
        $path = 'IssueCoverNote';
        // Format list additional driver
        $additional_driver = [];
        $index = 1;
        if(isset($input->additional_driver)) {
            foreach ($input->additional_driver as $additional_driver) {
                array_push($additional_driver, (object)[
                    'drvage' => intval($additional_driver->age - 18 < 0 ? 0 : $additional_driver->age - 18),
                    'drvdob' => formatDateFromIC($additional_driver->id_number),
                    'drvgender' => getGenderFromIC($additional_driver->id_number),
                    'drvmarital' => $input->marital_status,
                    'drvoccup' => $input->occupation,
                    'drvrel' => $input->relation ?? 'I',
                    'icnumber' => getAgeFromIC($additional_driver->id_number),
                    'index' => intval($index),
                    'name' => $input->name,
                    'oicnumber' => '',
                ]);
                $index++;
            }
        }
        // Format Extra Cover Code
        $formatted_extra_cover = [];
        if(isset($input->extra_cover)) {
            foreach ($input->extra_cover as $extra_cover) {
                $extra_cover_code = $extra_cover->extra_cover_code;
                $extra_cover->extra_cover_description = $this->getExtraBenefitDescription($extra_cover_code);

                if($extra_cover->extra_cover_code == '40') {
                    $extra_cover_code = $extra_cover->extra_cover_code . $this->getLltpCode($input->vehicle->engine_capacity);
                } else if($extra_cover->extra_cover_code == '112') {
                    $extra_cover_code = 'CART';
                    $extra_cover->sum_insured = $extra_cover->cart_amount;
                    $extra_cover->unit = $extra_cover->cart_day;
                } else if($extra_cover->extra_cover_code == 'EZ100A') {
                    $extra_cover->sum_insured = 10000;
                } else if($extra_cover->extra_cover_code == 'EZ103') {
                    $extra_cover->sum_insured = 1500;
                } else if($extra_cover->extra_cover_code == 'EZ106') {
                    $extra_cover->sum_insured = 500;
                } else if($extra_cover->extra_cover_code == 'EZ109') {
                    $extra_cover->sum_insured = 300;
                } else if($extra_cover->extra_cover_code == '57' || $extra_cover->extra_cover_code == '25') {
                    $extra_cover->sum_insured = $input->vehicle->sum_insured;
                }

                array_push($formatted_extra_cover, (object) [
                    'bencode' => $extra_cover_code,
                    'bendesc' => $extra_cover->extra_cover_description,
                    'suminsured' => $extra_cover->sum_insured ?? 0,
                    'unit' => $extra_cover->unit ?? 0,
                    'benpremium' => 0,
                    'cewcommperc' => '',
                    'cewstampduty' => '',
                ]);
            }
        }
        $item = [];        
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->nvic,
        ]);

        $dobs = str_split($input->id_number, 2);
        $id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
        $year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}
		$dob = $dobs[1] . "/" . $dobs[2] . "/" . strval($year);
        $effective_time = '00:00:01';
        if(Carbon::parse($input->vehicle->inception_date)->lessThan(Carbon::today())) {
            $effective_time = date('H:i:s', strtotime('now'));
        }

        $data = [
            'GPSCertNo' => '',
            'GPSCompName' => '',
            'actprem' => doubleval('157.95'),
            'address1' =>($input->address_one ?? '11 FLOOR AIK HUA'),
            'address2' => isset($input->address_two) ? (empty($input->address_two) ? $input->city . ', ' . $input->state : $input->address_two) : '',
            'address3' => isset($input->address_two) ? (empty($input->address_two) ? '' : $input->city . ', ' . $input->state) : '',
            'agentcode' => $this->agent_code,
            'agtgstregdate' => '',
            'agtgstregno' => '',
            'antitd' => self::ANTI_THEFT,
            'bankappcode' => '',
            'birthdate' => $dob,
            'bizregno' => '',
            'breakdownassist' => '',
            'campaigncode' => 'TT1',
            'ccardexpdt' => '201903',
            'ccardtype' => 'VISA',
            'channel' => 'TIB',
            'chassisno' => $input->vehicle->extra_attribute->chassis_number,
            'claimamt' => doubleval('0'),
            'cncondition' => 'u',
            'cnpaystatus' => 'C',
            'commgstamt' => doubleval('0'),
            'commiamt' => doubleval('296.59'),
            'commiperc' => doubleval('10'),
            'compcode' => '71',
            'country' => '',
            'covercode' => '01',
            'discount' => 'NO',
            'discountamt' => doubleval('0'),
            'discountperc' => doubleval('0'),
            'drvexp' => intval(getAgeFromIC($input->id_number) - 18),
            'effectivedate' => Carbon::parse($input->vehicle->inception_date)->format('Y-m-d'),
            'effectivetime' => $effective_time,
            'email' => $input->email,
            'engineno' => 'STG4567STG4567',
            'excess' => doubleval('770'),
            'expirydate' => Carbon::parse($input->vehicle->expiry_date)->format('Y-m-d'),
            'flquoteno' => 'FLTIB000001-001',
            'garage' => 'B',
            'gender' => 'M',
            'grossdue' => doubleval('3153.91'),
            'grossdue2' => doubleval('0'),
            'grossprem' => doubleval('2965.95'),
            'gstamt' => doubleval('177.96'),
            'gstclaimperc' => doubleval('0'),
            'gstcode' => 'T128',
            'gstoverwrite' => 'N',
            'gstperc' => doubleval('6'),
            'gstpurpose' => 'I',
            'gstreg' => 'N',
            'gstregdate' => '',
            'gstregdateend' => '',
            'gstregno' => '',
            'hpcode' => '001319',
            'hphoneno' => '',
            'insertstmp' => '',
            'insref2' => 'RescueCare',
            'last4digit' => '',
            'lessor' => '',
            'loadingamt' => doubleval('684.45'),
            'loadingperc' => doubleval('30'),
            'makecodemajor' => '31',
            'makecodeminor' => '08',
            'makeyear' => intval($input->vehicle->manufacture_year), 
            'maritalstatus' => $input->marital_status,
            'merchantid' => '',
            'name' => $input->name ?? config('app.name'),
            'ncdamt' => doubleval('0'),
            'ncdperc' => doubleval($input->vehicle->ncd_percentage),
            'netdue' => doubleval('2857.32'),
            'netdue2' => doubleval('0'),
            'netprem' => doubleval('2669.36'),
            'newic' => $id_number,
            'occupmajor' => '001',
            'oldic' => '',
            'ownershiptype' => 'I',
            'passportno' => '',
            'payamt' => '0',
            'paytype' => 'CC',
            'piamdrv' => '03',
            'postcode' => $input->postcode,
            'preinscode' => '',
            'preinsname' => '',
            'preinsncd' => doubleval('0.00'),
            'preinspolno' => '',
            'preinsregno' => 'STG4567',
            'prepoleffdate' => '',
            'prepolexpdate' => '',
            'pscoreoriloading' => doubleval('30'),
            'purchasedate' => '',
            'purchaseprice' => doubleval('0'),
            'purpose' => 'NB',
            'quoteno' => '',
            'receiptno' => '',
            'redbookdesc' => '',
            'redbooksum' => doubleval('0'),
            'region' => strtoupper(substr($input->region, 0, 1)),
            'regno' => 'STG4567',
            'renewno' => '',
            'requestid' => 'D112',
            'respdatetime' => '',
            'safety' => self::SAFETY_CODE,
            'signature' => $this->generateSignature('D112'),
            'stampduty' => doubleval('10'),
            'statecode' => '001',
            'suminsured' => doubleval($input->vehicle->sum_insured),
            'tariffpremium' => doubleval('2281.5'),
            'theftclaim' => intval('0'),
            'thirdclaim' => intval('0'),
            'towndesc' => $this->getStateName($input->state),
            'trailerno' => '',
            'usecode' => '01',
            'vehbody' => 'SEDAN',
            'vehbodycode' => '27',
            'vehcapacity' => doubleval($input->vehicle->engine_capacity),
            'vehcapacitycode' => self::VEHICLE_CAPACITY_CODE,
            'vehclaim' => intval('0'),
            'vehtypecode' => 'V010010',
            'waiveloading' => '',
            'winclaim' => intval('0'),
            'additional_driver' => $additional_driver,
            'formatted_extra_cover' => $formatted_extra_cover,
            'item' => $item,
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.issue_covernote')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $response,
        ]);
    }

    private function save_referredcase(object $input) : ResponseData
    {
        $path = 'SaveReferredCase';
        // Format list additional driver
        $additional_driver = [];
        $index = 1;
        if(isset($input->additional_driver)) {
            foreach ($input->additional_driver as $additional_driver) {
                array_push($additional_driver, (object)[
                    'drvage' => intval($additional_driver->age - 18 < 0 ? 0 : $additional_driver->age - 18),
                    'drvdob' => formatDateFromIC($additional_driver->id_number),
                    'drvgender' => getGenderFromIC($additional_driver->id_number),
                    'drvmarital' => $input->marital_status,
                    'drvoccup' => $input->occupation,
                    'drvrel' => $input->relation ?? 'I',
                    'icnumber' => getAgeFromIC($additional_driver->id_number),
                    'index' => intval($index),
                    'name' => $input->name,
                    'oicnumber' => '',
                ]);
                $index++;
            }
        }
        // Format Extra Cover Code
        $formatted_extra_cover = [];
        if(isset($input->extra_cover)) {
            foreach ($input->extra_cover as $extra_cover) {
                $extra_cover_code = $extra_cover->extra_cover_code;
                $extra_cover->extra_cover_description = $this->getExtraBenefitDescription($extra_cover_code);

                if($extra_cover->extra_cover_code == '40') {
                    $extra_cover_code = $extra_cover->extra_cover_code . $this->getLltpCode($input->vehicle->engine_capacity);
                } else if($extra_cover->extra_cover_code == '112') {
                    $extra_cover_code = 'CART';
                    $extra_cover->sum_insured = $extra_cover->cart_amount;
                    $extra_cover->unit = $extra_cover->cart_day;
                } else if($extra_cover->extra_cover_code == 'EZ100A') {
                    $extra_cover->sum_insured = 10000;
                } else if($extra_cover->extra_cover_code == 'EZ103') {
                    $extra_cover->sum_insured = 1500;
                } else if($extra_cover->extra_cover_code == 'EZ106') {
                    $extra_cover->sum_insured = 500;
                } else if($extra_cover->extra_cover_code == 'EZ109') {
                    $extra_cover->sum_insured = 300;
                } else if($extra_cover->extra_cover_code == '57' || $extra_cover->extra_cover_code == '25') {
                    $extra_cover->sum_insured = $input->vehicle->sum_insured;
                }

                array_push($formatted_extra_cover, (object) [
                    'bencode' => $extra_cover_code,
                    'bendesc' => $extra_cover->extra_cover_description,
                    'suminsured' => $extra_cover->sum_insured ?? 0,
                    'unit' => $extra_cover->unit ?? 0,
                    'benpremium' => 0,
                    'cewcommperc' => '',
                    'cewstampduty' => '',
                ]);
            }
        }
        $item = [];
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->nvic,
        ]);
        $dobs = str_split($input->id_number, 2);
        $id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
        $year = intval($dobs[0]);
		if ($year >= 10) {
			$year += 1900;
		} else {
			$year += 2000;
		}
		$dob = $dobs[1] . "/" . $dobs[2] . "/" . strval($year);
        $effective_time = '00:00:01';
        if(Carbon::parse($input->vehicle->inception_date)->lessThan(Carbon::today())) {
            $effective_time = date('H:i:s', strtotime('now'));
        }
        $data = [
            'GPSCertNo' => '',
            'GPSCompName' => '',
            'actprem' => doubleval('157.95'),
            'address1' =>($input->address_one ?? '11 FLOOR AIK HUA'),
            'address2' => isset($input->address_two) ? (empty($input->address_two) ? $input->city . ', ' . $input->state : $input->address_two) : '',
            'address3' => isset($input->address_two) ? (empty($input->address_two) ? '' : $input->city . ', ' . $input->state) : '',
            'agentcode' => $this->agent_code,
            'agtgstregdate' => '',
            'agtgstregno' => '',
            'antitd' => self::ANTI_THEFT,
            'bankappcode' => '',
            'birthdate' => $dob,
            'bizregno' => '',
            'breakdownassist' => '',
            'campaigncode' => 'TT1',
            'channel' => 'TIB',
            'chassisno' => 'STG4567STH4567',//$input->vehicle->extra_attribute->chassis_number,
            'claimamt' => doubleval('0'),
            'commgstamt' => doubleval('0'),
            'commiamt' => doubleval('296.59'),
            'commiperc' => doubleval('10'),
            'compcode' => '71',
            'country' => '',
            'covercode' => '01',
            'discount' => 'NO',
            'discountamt' => doubleval('0'),
            'discountperc' => doubleval('0'),
            'drvexp' => intval(getAgeFromIC($input->id_number) - 18),
            'effectivedate' => Carbon::parse($input->vehicle->inception_date)->format('d-m-Y'),
            'effectivetime' => $effective_time,
            'email' => $input->email,
            'engineno' => 'STG4567STG4567',
            'excess' => doubleval('770'),
            'expirydate' => Carbon::parse($input->vehicle->expiry_date)->format('d-m-Y'),
            'flquoteno' => 'FLTIB000001-001',
            'garage' => 'B',
            'gender' => 'M',
            'grossdue' => doubleval('3153.91'),
            'grossdue2' => doubleval('0'),
            'grossprem' => doubleval('2965.95'),
            'gstamt' => doubleval('177.96'),
            'gstclaimperc' => doubleval('0'),
            'gstcode' => 'T128',
            'gstoverwrite' => 'N',
            'gstperc' => doubleval('6'),
            'gstpurpose' => 'I',
            'gstreg' => 'N',
            'gstregdate' => '',
            'gstregdateend' => '',
            'hpcode' => '001319',
            'hphoneno' => '',
            'insertstmp' => '',
            'insref2' => 'RescueCare',
            'lessor' => '',
            'loadingamt' => doubleval('684.45'),
            'loadingperc' => doubleval('30'),
            'makecodemajor' => '31',
            'makecodeminor' => '08',
            'makeyear' => intval('1998'), //$input->vehicle->manufacture_year
            'maritalstatus' => $input->marital_status,
            'name' => $input->name ?? config('app.name'),
            'ncdamt' => doubleval('0'),
            'ncdperc' => doubleval($input->vehicle->ncd_percentage),
            'netdue' => doubleval('2857.32'),
            'netdue2' => doubleval('0'),
            'netprem' => doubleval('2669.36'),
            'newic' => $id_number,
            'occupmajor' => '001',
            'oldic' => '',
            'ownershiptype' => 'I',
            'passportno' => '',
            'payamt' => '0',
            'payno' => '',
            'piamdrv' => '03',
            'postcode' => $input->postcode,
            'preinscode' => '',
            'preinsname' => '',
            'preinsncd' => doubleval('0.00'),
            'preinspolno' => '',
            'preinsregno' => 'STG4567',
            'prepoleffdate' => '',
            'prepolexpdate' => '',
            'pscoreoriloading' => doubleval('30'),
            'purchasedate' => '',
            'purchaseprice' => doubleval('0'),
            'purpose' => 'NB',
            'quoteno' => '',
            'receiptno' => '',
            'redbookdesc' => '',
            'redbooksum' => doubleval('0'),
            'refercode' => '',
            'referdesc' => '',
            'region' => strtoupper(substr($input->region, 0, 1)),
            'regno' => 'STG4567',
            'renewno' => '',
            'requestid' => 'D112',
            'respdatetime' => '',
            'safety' => self::SAFETY_CODE,
            'signature' => $this->generateSignature('D112'),
            'stampduty' => doubleval('10'),
            'statecode' => '001',
            'suminsured' => doubleval($input->vehicle->sum_insured),
            'tariffpremium' => doubleval('2281.5'),
            'theftclaim' => intval('0'),
            'thirdclaim' => intval('0'),
            'towndesc' => $this->getStateName($input->state),
            'trailerno' => '',
            'usecode' => '01',
            'vehbody' => 'SEDAN',
            'vehbodycode' => '27',
            'vehcapacity' => doubleval($input->vehicle->engine_capacity),
            'vehcapacitycode' => self::VEHICLE_CAPACITY_CODE,
            'vehclaim' => intval('0'),
            'vehtypecode' => 'V010010',
            'waiveloading' => '',
            'winclaim' => intval('0'),
            'additional_driver' => $additional_driver,
            'formatted_extra_cover' => $formatted_extra_cover,
            'item' => $item,
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.save_referredcase')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $result_data,
        ]);
    }

    private function get_referredlisting(object $input) : ResponseData
    {
        $path = 'GetReferredListing';
        $item = [];
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->nvic,
        ]);

        $data = [
            'agentcode' => $this->agent_code,
            'bizregno' => '',
            'compcode' => '71',
            'newic' => $input->id_number,
            'oldic' => '',
            'passportno' => '',
            'requestid' => 'D112',
            'signature' => $this->generateSignature('D112'),
            'vehregno' => $input->vehicle_number,
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.get_referredlisting')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $result_data,
        ]);
    }

    private function get_referreddata(object $input) : ResponseData
    {
        $path = 'GetReferredListing';
        $item = [];
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->nvic,
        ]);

        $data = [
            'agentcode' => $this->agent_code,
            'compcode' => '71',
            'requestid' => 'D112',
            'signature' => $this->generateSignature('D112'),
            'vehregno' => $input->vehicle_number,
            'flquoteno' => 'FLTIB000001-001',
            'quoteno' => '5745201509211032csfong',
            'respdatetime' => '',
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.get_referreddata')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $result_data,
        ]);
    }

    private function get_jpjstatus_listing(object $input) : ResponseData
    {
        $path = 'GetJPJStatusListing';
        $item = [];
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->nvic,
        ]);

        $data = [
            'agentcode' => $this->agent_code,
            'bizregno' => '',
            'compcode' => '71',
            'newic' => $input->id_number,
            'oldic' => '',
            'passportno' => '',
            'requestid' => 'D112',
            'signature' => $this->generateSignature('D112'),
            'vehregno' => $input->vehicle_number,
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.get_jpjstatus_listing')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $result_data,
        ]);
    }

    private function update_jpjstatus(object $input) : ResponseData
    {
        $path = 'UpdJPJStatus';
        $item = [];
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->nvic,
        ]);

        $data = [
            'agentcode' => $this->agent_code,
            'bizregno' => '',
            'compcode' => '71',
            'newic' => $input->id_number,
            'oldic' => '',
            'passportno' => '',
            'requestid' => 'D112',
            'signature' => $this->generateSignature('D112'),
            'vehregno' => $input->vehicle_number,
            'chassisno' => $input->vehicle->extra_attribute->chassis_number,
            'cncondition' => 'u',
            'covernoteno' => '300120',
            'engineno' => $input->vehicle->extra_attribute->engine_number,
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.update_jpjstatus')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $result_data,
        ]);
    }

    private function get_renewaldata(object $input) : ResponseData
    {
        $path = 'GetRenewalData';
        $item = [];
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->nvic,
        ]);

        $data = [
            'agentcode' => $this->agent_code,
            'bizregno' => '',
            'policyno' => '5745201509211030',
            'compcode' => '71',
            'newic' => $input->id_number,
            'oldic' => '',
            'passportno' => '',
            'requestid' => 'D112',
            'signature' => $this->generateSignature('D112'),
            'vehregno' => $input->vehicle_number,
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.get_renewaldata')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $result_data,
        ]);
    }

    private function get_policyprint_listing(object $input) : ResponseData
    {
        $path = 'GetPolicyPrintListing';
        $item = [];
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->nvic,
        ]);

        $data = [
            'agentcode' => $this->agent_code,
            'bizregno' => '',
            'compcode' => '71',
            'newic' => $input->id_number,
            'oldic' => '',
            'passportno' => '',
            'requestid' => 'D112',
            'signature' => $this->generateSignature('D112'),
            'vehregno' => $input->vehicle_number,
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.get_policyprint_listing')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $result_data,
        ]);
    }

    private function get_policydata(object $input) : ResponseData
    {
        $path = 'GetPolicyData';
        $item = [];
        array_push($item,(object)[
            'paramIndicator' => 'NVIC',
            'paramRemark' => '',
            'paramValue' => $input->nvic,
        ]);

        $data = [
            'compcode' => '71',
            'flquoteno' => 'FLTIB000001-001',
            'quoteno' => '5745201509211032csfong',
            'requestid' => 'D112',
            'signature' => $this->generateSignature('D112'),
            'vehregno' => $input->vehicle_number,
        ];
        // Generate XML from view
        $xml = view('backend.xml.aig.get_policydata')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $result_data,
        ]);
    }

    private function getStateName($state) : string
    {
        $state_name = '';

        switch (strtolower($state)) {
            case 'johor': {
                    $state_name = 'JOHOR';
                    break;
                }
            case 'kedah': {
                    $state_name = 'KEDAH';
                    break;
                }
            case 'kelantan': {
                    $state_name = 'KELANTAN';
                    break;
                }
            case 'wilayah persekutuan kuala lumpur': {
                    $state_name = 'W.P KUALA LUMPUR';
                    break;
                }
            case 'wilayah persekutuan labuan': {
                    $state_name = 'W.P LABUAN';
                    break;
                }
            case 'melaka': {
                    $state_name = 'MELAKA';
                    break;
                }
            case 'negeri sembilan': {
                    $state_name = 'NEGERI SEMBILAN';
                    break;
                }
            case 'pahang': {
                    $state_name = 'PAHANG';
                    break;
                }
            case 'wilayah persekutuan putrajaya': {
                    $state_name = 'W.P PUTRAJAYA';
                    break;
                }
            case 'perlis': {
                    $state_name = 'PERLIS';
                    break;
                }
            case 'pulau pinang': {
                    $state_name = 'PULAU PINANG';
                    break;
                }
            case 'perak': {
                    $state_name = 'PERAK';
                    break;
                }
            case 'sabah': {
                    $state_name = 'SABAH';
                    break;
                }
            case 'selangor': {
                    $state_name = 'SELANGOR';
                    break;
                }
            case 'sarawak': {
                    $state_name = 'SARAWAK';
                    break;
                }
            case 'terengganu': {
                    $state_name = 'TERENGGANU';
                    break;
                }
        }

        return $state_name;
    }

    private function getBodyTypeDetails($body_type_id) : object
    {
        $body_type = (object) [
            'code' => '',
            'description' => '',
        ];

        switch($body_type_id) {
            case '2': {
                    $body_type->code = '04';
                    $body_type->description = 'PICK UP';

                    break;
                }
            case '3': {
                    $body_type->code = '06';
                    $body_type->description = 'COUPE';

                    break;
                }
            case '4': {
                    $body_type->code = '18';
                    $body_type->description = 'HATCHBACK';

                    break;
                }
            case '5': {
                    $body_type->code = '16';
                    $body_type->description = 'MPV';

                    break;
                }
            case '6': {
                    $body_type->code = '33';
                    $body_type->description = 'SUV';

                    break;
                }
            default: {
                    $body_type->code = '01';
                    $body_type->description = 'SALOON';
                }
        }

        return $body_type;
    }

    private function getExtraBenefitDescription($extra_cover_code, $engine_capacity = null) : string
    {
        $description = '';

        switch ($extra_cover_code) {
            case '07': {
                    $description = 'Named Persons';
                    break;
                }
            case '101': {
                    $description = 'Extension Cover To The Kingdom Of Thailand';
                    break;
                }
            case '105': {
                    $description = 'Limits Of Liability For Third Party Property Damage';
                    break;
                }
            case '111': {
                    $description = 'NCD Relief Cover';
                    break;
                }
            case '112': {
                    $description = 'Compensation for Assessed Repair Time (CART)';
                    break;
                }
            case '25': {
                    $description = 'Strike, Riot And Civil Commotion';
                    break;
                }
            case '40': {
                    $description = 'Legal Liability To Passengers';
                    if($engine_capacity <= 1400) {
                        $description .= ' (Up To 1400 CC)';
                    } else if($engine_capacity > 1400 && $engine_capacity <= 1650) {
                        $description .= ' (1401-1650 CC)';
                    } else if($engine_capacity > 1650 && $engine_capacity <= 2200) {
                        $description .= ' (1651-2200 CC)';
                    } else if($engine_capacity > 2200 && $engine_capacity <= 3050) {
                        $description .= ' (2201-3050 CC)';
                    } else if($engine_capacity > 3050 && $engine_capacity <= 4100) {
                        $description .= ' (3051-4400 CC)';
                    } else if($engine_capacity > 4100 && $engine_capacity <= 4250) {
                        $description .= ' (4101-4250 CC)';
                    } else if($engine_capacity > 4250 && $engine_capacity <= 4400) {
                        $description .= ' (4251-4400 CC)';
                    } else if($engine_capacity > 4400) {
                        $description .= ' (Over 4400 CC)';
                    }
                    break;
                }
            case '57': {
                    $description = 'Special Perils';
                    break;
                }
            case '72': {
                    $description = 'Legal Liability Of Passengers';
                    break;
                }
            case '89': {
                    $description = 'Windscreen';
                    break;
                }
            case '97': {
                    $description = 'Multimedia Player Or Other Accessories';
                    break;
                }
            case '97A': {
                    $description = 'Gas Conversion Kit And Tank';
                    break;
                }
            case '10': {
                    $description = 'All Drivers';
                    break;
                }
        }
        

        return $description;
    }

    private function sortExtraCoverList(array $extra_cover_list) : array
    {
        foreach ($extra_cover_list as $_extra_cover) {
            $sequence = 99;

            switch ($_extra_cover->extra_cover_code) {
                case '10': { // All Drivers
                        $sequence = 1;

                        break;
                    }
                case '89': { // Windscreen
                        $sequence = 2;

                        break;
                    }
                case '40': { // Legal Liability to Passengers
                        $sequence = 3;

                        break;
                    }
                case '72': { // Legal Liability of Passengers for Negligent Acts (LLAN)
                        $sequence = 4;

                        break;
                    }
                case '57': { // Special Perils
                        $sequence = 5;

                        break;
                    }
                case '25': { // Strike Riot & Civil Commotion
                        $sequence = 6;

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

    public function cURL(string $path, string $xml, string $soap_action = null, string $method = 'POST', array $header = []) : ResponseData
    {
        // Concatenate URL
        $url = $this->url .'/'. $path.'?wsdl';
        
        // Check XML Error
        libxml_use_internal_errors(true);

        // Construct API Request
        $request_options = [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/xml',
                'Accept' => 'application/xml',
                // 'Content-Type' => 'text/xml; charset=utf-8',
                // 'Accept' => 'text/xml; charset=utf-8',
                'SOAPAction' => $this->url .$path.'?wsdl',
            ],
            'body' => $xml
        ];
        
        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $this->url,
            'path' => $path,
            'request_header' => json_encode($request_options['headers']),
            'request' => json_encode($request_options['body']),
        ]);

        $result = HttpClient::curl($method, $url, $request_options);
        // Update the API log
        APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => $result->response
            ]);
        if($result->status) {
            $cleaned_xml = preg_replace('/(<\/|<)[a-zA-Z]+:([a-zA-Z0-9]+[ =>])/', '$1$2', $result->response);
            $response = simplexml_load_string($cleaned_xml);
            if($response === false) {
                return $this->abort(__('api.xml_error'));
            }

            $response = $response->xpath('Body')[0];
        } else {
            $message = '';
            if(empty($result->response)) {
                $message = __('api.empty_response', ['company' => $this->company_name]);
            } else {
                if(is_string($result->response)) {
                    $message = $result->response;
                } else {
                    $message = 'An Error Encountered. ' . json_encode($result->response);
                }
            }

            return $this->abort($message);
        }

        return new ResponseData([
            'status' => $result->status,
            'response' => $response
        ]);
    }
}