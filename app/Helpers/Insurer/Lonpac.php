<?php

namespace App\Helpers\Insurer;

use App\DataTransferObjects\Motor\CartList;
use App\DataTransferObjects\Motor\ExtraCover;
use App\DataTransferObjects\Motor\OptionList;
use App\DataTransferObjects\Motor\Response\PremiumResponse;
use App\DataTransferObjects\Motor\Response\ResponseData;
use App\DataTransferObjects\Motor\Response\VIXNCDResponse;
use App\DataTransferObjects\Motor\VariantData;
use App\DataTransferObjects\Motor\Vehicle;
use App\Helpers\HttpClient;
use App\Interfaces\InsurerLibraryInterface;
use App\Models\APILogs;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Postcode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\EGHLLog;

class Lonpac implements InsurerLibraryInterface
{
    private string $host;
    private string $agent_code;
    private string $secret_key;
    private string $company_id;
    private string $company_name;
    private string $company_code;

    // Settings
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 750000;
    private const TIMESTAMP_FORMAT = 'Y/m/d H:i:s';
    private const EXTRA_BENEFIT_LIST = [
        'M02', 'M17', 'M01', 'M11', 'M03', 'M51', 'M68', 'M65', 'M66', 'M67', 'M62'
    ];
    private const CART_AMOUNT_LIST = [50, 100, 200];
    private const CART_DAY_LIST = [7, 14, 21];
    private const ANTI_THEFT = '03'; // Without M.Device - Factory Fitted Alarm
    private const COVER_CODE = 'COMP'; // Comprehensive
    private const GARAGE_CODE = '3'; // Locked Compound
    private const GST_CODE = 'SST';
    private const OCCUPATION_CODE = '040'; // Others (In Group)
    private const OCCUPATION = 'OTHERS'; // Others (In Group)
    private const PIAM_DRIVER = '03'; // All Drivers
    private const PURPOSE = 'NB'; // New Business
    private const SAFETY_CODE = '06'; // ABS & Airbags 2
    private const VEHICLE_CAPACITY_CODE = 'CC';
    private const COMPANY_CODE = '11';

    public function __construct(int $insurer_id, string $insurer_name)
    {
        $this->company_id = $insurer_id;
        $this->company_name = $insurer_name;

        $this->host = config('insurer.config.lonpac.host');
        $this->agent_code = config('insurer.config.lonpac.agent_code');
        $this->secret_key = config('insurer.config.lonpac.secret_key');
        $this->company_code = config('insurer.config.lonpac.company_code');
    }

    public function vehicleDetails(object $input) : object
    {
        $id_number = $company_registration_number = '';
        switch ($input->id_type) {
            case config('setting.id_type.nric_no'): {
                $id_number = $input->id_number;

                break;
            }
            case config('setting.id_type.company_registration_no'): {
                $company_registration_number = $input->id_number;

                break;
            }
            default: {
                return $this->abort(__('api.unsupported_id_type'), config('setting.response_codes.unsupported_id_types'));
            }
        }

        $data = (object) [
            'vehicle_number' => $input->vehicle_number,
            'id_type' => $input->id_type,
            'id_number' => $id_number,
            'postcode' => $input->postcode,
            'state' => $input->state,
            'company_registration_number' => $company_registration_number
        ];

        $vehicle_vix = $this->getVIXNCD($data);
        //Log::info("[API/GetVehicleDetails] Dzul Request Test: " . json_encode($vehicle_vix));
        if (!$vehicle_vix->status) {
            return $this->abort($vehicle_vix->response, $vehicle_vix->code);
        }

        // Get coverage dates
        $inception_date = Carbon::parse($vehicle_vix->response->expiry_date)->addDay();
        $expiry_date = Carbon::parse($inception_date)->addYear()->subDay()->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');

        // Check Inception Date
        if ($inception_date < $today) {
            $gap_in_cover = abs(Carbon::today()->diffInDays($inception_date));
            // Liberty Doesn't Allow Renewal for Expired Policy
            if ($gap_in_cover > 0) {
                return $this->abort(__('api.gap_in_cover', ['days' => $gap_in_cover]), config('setting.response_codes.gap_in_cover'));
            }

            $inception_date = Carbon::parse($today);
            $expiry_date = Carbon::today()->addYear()->subDay()->format('Y-m-d');
        } else {
            // Check 2 Months Before
            if (Carbon::parse($today)->addMonths(2)->lessThan($inception_date)) {
                return $this->abort(__('api.earlier_renewal'), config('setting.response_codes.earlier_renewal'));
            }
        } 
        /* 
        // Check Sum Insured
        $sum_insured = formatNumber($vehicle_vix->response->sum_insured, 0);
        if ($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(
                __('api.sum_insured_referred_between', ['min_sum_insured' => self::MIN_SUM_INSURED, 'max_sum_insured' => self::MAX_SUM_INSURED]),
                config('setting.response_codes.sum_insured_referred')
            );
        } */

        $nvic = explode('|', (string) $vehicle_vix->response->nvic);
        $variant = explode('|', (string) $vehicle_vix->response->variant);
        $variants = [];
        $count = 0;
        foreach($nvic as $_nvic) {
            array_push($variants, new VariantData([
                'nvic' => $_nvic,
                'sum_insured' => floatval($vehicle_vix->response->sum_insured),
                'variant' => $variant[$count] ?? ''
            ]));
            $count += 1;
        }

        return (object) [
            'status' => true,
            'response' => new VIXNCDResponse([
                'chassis_number' => (string) $vehicle_vix->response->chassis_number,
                //'class_code' => $vehicle_vix->response->class_code,
                'coverage' => 'Comprehensive',
                'cover_type' => (string) $vehicle_vix->response->coverage_code,
                'engine_capacity' => (int) $vehicle_vix->response->engine_capacity,
                'engine_number' => (string) $vehicle_vix->response->engine_number,
                'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
                'inception_date' => $inception_date->format('d M Y'),
                //'liberty_model_code' => $vehicle_vix->response->liberty_model_code,
                'make' => $vehicle_vix->response->make,
                'manufacture_year' => $vehicle_vix->response->manufacture_year,
                'make_code' => intval($vehicle_vix->response->make_code),
                'max_sum_insured' => floatval($vehicle_vix->response->max_sum_insured),
                'min_sum_insured' => floatval($vehicle_vix->response->min_sum_insured),
                'model' => $vehicle_vix->response->model,
                'model_code' => intval($vehicle_vix->response->model_code),
                'ncd_percentage' => $vehicle_vix->response->ncd_percentage,
                'seating_capacity' => $vehicle_vix->response->seating_capacity,
                'sum_insured' => floatval($vehicle_vix->response->sum_insured),
                'sum_insured_type' => $vehicle_vix->response->sum_insured_type,
                'variants' => $variants,
                'vehicle_number' => $input->vehicle_number,
                'vehicle_use_code' => $vehicle_vix->response->vehicle_use_code,
                'vehicle_type_code' => $vehicle_vix->response->vehicle_class_code,
                'windscreen_franchise' =>  $vehicle_vix->response->windscreen_franchise,
                'windscreen_local' =>  $vehicle_vix->response->windscreen_local,
                'ins_code' =>  $vehicle_vix->response->ins_code,
                'pre_ins_code' =>  $vehicle_vix->response->pre_ins_code,
            ])
        ];
    }

    public function premiumDetails(object $input, $full_quote = false) : object
    {
        $vehicle = $input->vehicle ?? null;
        $basic_premium = $ncd_percentage = $ncd_amount = $total_benefit_amount = $gross_premium = $sst_amount = $sst_percent = $stamp_duty = $excess_amount = $total_payable = $net_premium = 0;
        $id_number = $company_registration_number = $ownership_type = $date_of_birth = $gender = $marital_status = '';
        $allowed_extra_cover = self::EXTRA_BENEFIT_LIST;
        $driving_experience = 0;
        switch($input->id_type) {
            case config('setting.id_type.nric_no'): {
                $id_number = $input->id_number;
                $ownership_type = 'I'; // Individual
                $date_of_birth = formatDateFromIC($input->id_number);
                $driving_experience = getAgeFromIC($input->id_number) - 18 < 0 ? 18 : getAgeFromIC($input->id_number) - 18;
                $gender = $input->gender;
                $marital_status = $input->marital_status;

                $allowed_extra_cover = array_filter(self::EXTRA_BENEFIT_LIST, function($code) {
                    return $code !== '10';
                });

                break;
            }
            case config('setting.id_type.company_registration_no'): {
                $company_registration_number = $input->id_number;
                $ownership_type = $gender = $marital_status = 'C'; // Corporate

                break;
            }
            default: {
                return $this->abort(__('api.unsupported_id_type'), config('setting.response_codes.unsupported_id_types'));
            }
        }

        if ($full_quote) {
            $vehicle_detail = $this->vehicleDetails($input);
            if (!$vehicle_detail->status) {
                return $this->abort($vehicle_detail->response, $vehicle_detail->code);
            }

            // Get Selected Variant
            $selected_variant = null;
            if ($input->nvic == '-') {
                if (count($vehicle_detail->response->variants) == 1) {
                    $selected_variant = $vehicle_detail->response->variants[0];
                }
            } else {
                foreach ($vehicle_detail->response->variants as $_variant) {
                    if ($input->nvic == $_variant->nvic) {
                        $selected_variant = $_variant;
                        break;
                    }
                }
            }

            if (empty($selected_variant)) {
                return $this->abort(__('api.variant_not_match'));
            }

            $vehicle = new Vehicle([
                'coverage' => $vehicle_detail->response->coverage,
                'engine_capacity' => $vehicle_detail->response->engine_capacity,
                'expiry_date' => Carbon::createFromFormat('d M Y', $vehicle_detail->response->expiry_date)->format('Y-m-d'),
                'extra_attribute' => (object) [
                    'body_type_code' => $vehicle_detail->response->body_type_code,
                    'body_type_description' => $vehicle_detail->response->body_type_description,
                    'chassis_number' => $vehicle_detail->response->chassis_number,
                    'cover_type' => $vehicle_detail->response->cover_type,
                    'engine_number' => $vehicle_detail->response->engine_number,
                    //'liberty_model_code' => $vehicle_detail->response->liberty_model_code,
                    'make_code' => $vehicle_detail->response->make_code,
                    'model_code' => $vehicle_detail->response->model_code,
                    'seating_capacity' => $vehicle_detail->response->seating_capacity,
                    'vehicle_use_code' => $vehicle_detail->response->vehicle_use_code,
                    'vehicle_type_code' => $vehicle_detail->response->vehicle_type_code,
                    'windscreen_franchise' =>  $vehicle_detail->response->windscreen_franchise,
                    'windscreen_local' =>  $vehicle_detail->response->windscreen_local,
                    'ins_code' =>  $vehicle_detail->response->ins_code,
                    'pre_ins_code' => $vehicle_detail->response->pre_ins_code,
                ],
                'inception_date' => Carbon::createFromFormat('d M Y', $vehicle_detail->response->inception_date)->format('Y-m-d'),
                'make' =>  $vehicle_detail->response->make,
                'manufacture_year' => $vehicle_detail->response->manufacture_year,
                'max_sum_insured' => $vehicle_detail->response->max_sum_insured,
                'min_sum_insured' => $vehicle_detail->response->min_sum_insured,
                'model' => $vehicle_detail->response->model,
                'ncd_percentage' => $vehicle_detail->response->ncd_percentage,
                //'cur_ncd_percentage' => $vehicle_detail->response->cur_ncd_percentage,
                'nvic' => $selected_variant->nvic,
                'sum_insured_type' => $vehicle_detail->response->sum_insured_type,
                'sum_insured' => $vehicle_detail->response->sum_insured,
                'variant' => $selected_variant->variant,
            ]);

            $data = (object) [
                'gender' => $gender,
                'dob' => $date_of_birth,
                'driving_experience' => $driving_experience,
                'id_number' => $id_number,
                'company_registration_number' => $company_registration_number,
                'marital_status' => $marital_status,
                'postcode' => $input->postcode,
                'region' => $input->region,
                'state' => $input->state,
                'email' => $input->email,
                'vehicle' => $vehicle,
                'vehicle_number' => $input->vehicle_number,
                'ownership_type' => $ownership_type
            ];

            $motor_premium = $this->getPremium($data);

            if (!$motor_premium->status) {
                return $this->abort($motor_premium->response);
            }

            $ncd_amount = $motor_premium->response->ncd_amount;
            $basic_premium = formatNumber($motor_premium->response->gross_premium + $ncd_amount);
            $ncd_percentage = $motor_premium->response->ncd_percentage;
            $total_benefit_amount = 0;
            $gross_premium = $motor_premium->response->gross_premium;
            $sst_percent = $motor_premium->response->sst_percentage;
            $sst_amount = $motor_premium->response->sst_amount;
            $stamp_duty = $motor_premium->response->stamp_duty;
            $excess_amount = $motor_premium->response->excess;
            $total_payable = $motor_premium->response->gross_due;
            $net_premium = formatNumber($motor_premium->response->net_premium + $sst_amount + $stamp_duty);

            // Generate extra cover list and Include the generated extra cover list to input
            $extra_cover_list = [];
            foreach ($allowed_extra_cover as $_extra_cover_code) {
                $extra_cover = new ExtraCover([
                    'selected' => false,
                    'readonly' => false,
                    'extra_cover_code' => $_extra_cover_code,
                    'extra_cover_description' => $this->getExtraBenefitDescription($_extra_cover_code, $vehicle->engine_capacity),
                    'premium' => 0,
                    'sum_insured' => 0,
                ]);

                $_sum_insured_amount = 0;

                switch($_extra_cover_code) {
                    case 'M51': { // CART
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
                        //Log::info("[API/GetPremium] Dzul Request Test: " . json_encode($cart_list));
                        $extra_cover->cart_list = $cart_list;

                        // Get The Lowest CART Day & Amount / Day To Get Premium
                        $_cart_day = $cart_list[0]->cart_day;
                        $_cart_amount = $cart_list[0]->cart_amount_list[0];
                        break;
                        }
                    case 'M02': { // Windscreen
                            // generate options from 300 to 10,000
                            $option_list = new OptionList([
                                'name' => 'sum_insured',
                                'description' => 'Sum Insured Amount',
                                'values' => generateExtraCoverSumInsured(500, 10000, 1000), //Checking balik windscreen local
                                'any_value' => true,
                                'increment' => 100
                            ]);

                            $extra_cover->option_list = $option_list;

                            // Default to RM1,000 to Get Premium
                            $_sum_insured_amount = $option_list->values[1];
                            //$extra_cover->premium = 300;
                            //$_sum_insured_amount = 800;
                            break;
                        }
                    case 'M17': { // Inclusion of Special Perils (Flood)
                        $_sum_insured_amount = $vehicle->sum_insured;
                        break;
                    }
                    case 'M01': {// Legal Liability Of Passengers
                        break;
                    }
                    case 'M11': {// LLP for negligent acts
                        break;
                    }
                    case 'M68': {// Butterment Buyback
                        break;
                    }
                    case 'M65': {// Replacement Cost of Car Keys
                        break;
                    }
                    case 'M66': {// Cleaning Cost of Vehicle
                        break;
                    }
                    case 'M67': {// Complete Respray of car
                        break;
                    }
                    case 'M03': { // Strike Riot & Civil Commotion
                        $_sum_insured_amount = $vehicle->sum_insured;
                        break;
                    }
                    case 'M62': { // Strike Riot & Civil Commotion
                        break;
                    }
                }

                if(!empty($_sum_insured_amount)) {
                    $extra_cover->sum_insured = $_sum_insured_amount;
                    //Log::info("[API/GetExtraCover3] Dzul Request Select: " . json_encode($_sum_insured_amount));
                } else if(!empty($_cart_day) && !empty($_cart_amount)) {
                    $extra_cover->cart_day = $_cart_day;
                    $extra_cover->cart_amount = $_cart_amount;
                }

                array_push($extra_cover_list, $extra_cover);
            }

            // Include Extra Covers to Get Premium
            $input->extra_cover = $extra_cover_list;
            // $descriptions = array_column($input->extra_cover, 'extra_cover_description');

            // // Implode the values using comma as separator
            // $imploded = implode('|', $descriptions);
            // Log::info("[API/GetBenefit] Dzul Request Test: " . json_encode($imploded));
            // Log::info("[API/COver] Dzul Request Test: " . json_encode($input->extra_cover));
        }
        //Log::info("[API/Input] Dzul Request Test: " . json_encode($input));
        $data = (object) [
            'dob' => $date_of_birth,
            'driving_experience' => $driving_experience,
            'email' => $input->email,
            'extra_cover' => $input->extra_cover,
            'gender' => $input->gender,
            'id_number' => $id_number,
            'company_registration_number' => $company_registration_number,
            'marital_status' => $input->marital_status,
            'postcode' => $input->postcode,
            'region' => $input->region,
            'state' => $input->state,
            'sum_insured' => $vehicle->sum_insured,
            'vehicle' => $vehicle,
            'vehicle_number' => $input->vehicle_number,
            'vehicle_body_type' => $input->vehicle_body_type ?? '',
            'ownership_type' => $ownership_type,
            'phone_number' => $input->phone_number
        ];
        $motor_premium = $this->getPremium($data);

/*         if (!$motor_premium->status) {
            if($motor_premium->code != 'T98' && $motor_premium->code != 'U80') { // Special Perils referred
                return $this->abort($motor_premium->response);
            }

            // Remove Special Perils & Try Again
            foreach($input->extra_cover as $key => $extra_cover) {
                if($extra_cover->extra_cover_code == '57') {
                    unset($input->extra_cover[$key]);
                }
            }

            $data->extra_cover = $input->extra_cover;
            $motor_premium = $this->getPremium($data);

            if (!$motor_premium->status) {
                return $this->abort($motor_premium->response);
            }
        }
*/
        if(isset($motor_premium->response->extra_benefit)) {
            $count1 = 0;
            $count = 0;
            $item = explode("|", $motor_premium->response->extra_benefit);
            foreach($item as $items) {
                $total_benefit_amount += formatNumber($items);
                $count1 = 0;
                foreach($input->extra_cover as $extra_cover) {
                    if($count == $count1){
                        $extra_cover->premium = formatNumber($items);
                        $extra_cover->selected = floatval($items) == 0;
                    }
                    $count1++;
                }
                $count++;
            }
        } 

        //Log::info("[API/GetExtraCover4] Dzul Request Select: " . json_encode($input->extra_cover));

        $response = new PremiumResponse([
            'basic_premium' => formatNumber(($motor_premium->response->gross_premium + $motor_premium->response->ncd_amount) - $total_benefit_amount),
            'excess_amount' => formatNumber($motor_premium->response->excess),
            'extra_cover' => $input->extra_cover,
            'gross_premium' => formatNumber($motor_premium->response->gross_premium),
            'max_sum_insured' => formatNumber($vehicle->max_sum_insured),
            'min_sum_insured' => formatNumber($vehicle->min_sum_insured),
            'ncd_amount' => formatNumber($motor_premium->response->ncd_amount),
            'ncd_percentage' => formatNumber($motor_premium->response->ncd_percentage),
            'net_premium' => formatNumber($motor_premium->response->net_premium + $motor_premium->response->sst_amount + $motor_premium->response->stamp_duty),
            'sst_amount' => formatNumber($motor_premium->response->sst_amount),
            'sst_percent' => formatNumber($motor_premium->response->sst_percentage),
            'stamp_duty' => formatNumber($motor_premium->response->stamp_duty),
            'sum_insured_type' => $vehicle->sum_insured_type,
            'sum_insured' => formatNumber($vehicle->sum_insured),
            'total_benefit_amount' => formatNumber($total_benefit_amount),
            'total_payable' => formatNumber($motor_premium->response->gross_due),
            //'fl_quote_number' => $motor_premium->response->fl_quote_number,
            //'named_drivers_needed' => $input->id_type === config('setting.id_type.nric_no')
            //'quotation_number' => $add_quote->response->quotationNo,
            'named_drivers_needed' => false
        ]);

        if ($full_quote) {
            // Revert to premium without extra covers
            $response->basic_premium = $basic_premium;
            $response->excess_amount = $excess_amount;
            $response->gross_premium = $gross_premium;
            $response->ncd_amount = $ncd_amount;
            $response->ncd_percentage = $ncd_percentage;
            $response->net_premium = $net_premium;
            $response->sst_amount = $sst_amount;
            $response->sst_percent = $sst_percent;
            $response->stamp_duty = $stamp_duty;
            $response->total_benefit_amount = 0;
            $response->total_payable = $total_payable;

            $response->vehicle = $vehicle;
        }

        return (object) [
            'status' => true,
            'response' => $response
        ];
    }

    public function quotation(object $input) : object
    {
        $data = (object) [
            //'additional_driver' => $input->additional_driver,
            'address_one' => $input->address_one,
            'address_two' => $input->address_two,
            'building_name' => $input->building_name ?? '',
            'city' => $input->city,
            'date_of_birth' => $input->date_of_birth,
            'email' => $input->email,
            'extra_cover' => $input->extra_cover,
            'gender' => $input->gender,
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'marital_status' => $input->marital_status,
            'name' => $input->name,
            'phone_code' => $input->phone_code,
            'phone_number' => $input->phone_number,
            'postcode' => $input->postcode,
            'region' => $input->region,
            'state' => $input->state,
            'unit_no' => $input->unit_no ?? '',
            'vehicle' => $input->vehicle,
            'vehicle_body_type' => $input->vehicle_body_type,
            'vehicle_number' => $input->vehicle_number,
        ];

        $quotation_result = $this->premiumDetails($data);

        if (!$quotation_result->status) {
            return $this->abort($quotation_result->response);
        }

        // Insert the quotation number returned from Liberty
        $quotation_result->response->quotation_number = $quotation_result->response->fl_quote_number;

        return (object) [
            'status' => true,
            'response' => $quotation_result->response
        ];
    }

    public function submission(object $input) : object
    {
        // Get Extra Attribute
        $extra_attribute = json_decode($input->insurance->extra_attribute->value);

        // Include Policy Holder Details
        $input->name = $input->insurance->holder->name;
        $input->address_one = $input->insurance->address->address_one;
        $input->address_two = $input->insurance->address->address_two;
        $input->city = $input->insurance->address->city;
        $input->postcode = $input->insurance->address->postcode;
        $input->state = $input->insurance->address->state;
        $input->phone_number = $input->insurance->holder->phone_number;
        $input->gender = $input->insurance->holder->gender;
        $input->marital_status = $input->insurance_motor->marital_status;

        switch($input->insurance->holder->id_type_id) {
            case config('setting.id_type.nric_no'): {
                $input->ownership_type = 'I'; // Individual
                $input->company_registration_number = '';
                $input->dob = formatDateFromIC($input->id_number);
                $input->driving_experience = $input->insurance->holder->age - 18 < 0 ? 0 : $input->insurance->holder->age - 18;

                break;
            }
            case config('setting.id_type.company_registration_no'): {
                $input->company_registration_number = $input->id_number;
                $input->ownership_type = 'C'; // Corporate
                $input->driving_experience = 0;
                $input->dob = '';

                break;
            }
            default: {
                return $this->abort(__('api.unsupported_id_type'), config('setting.response_codes.unsupported_id_types'));
            }
        }

        // Generate Additional Driver List
        // $additional_driver_list = [];
        // foreach ($input->insurance_motor->driver as $driver) {
        //     $additional_driver = (object) [
        //         'age' => getAgeFromIC($driver->id_number),
        //         'birth_date' => formatDateFromIC($driver->id_number),
        //         'driving_exp' => getAgeFromIC($driver->id_number) - 18 < 0 ? 0 : getAgeFromIC($driver->id_number) - 18,
        //         'gender' => getGenderFromIC($driver->id_number),
        //         'id_number' => $driver->id_number,
        //         'name' => $driver->name,
        //         'relationship' => $driver->relationship_id,
        //     ];

        //     array_push($additional_driver_list, $additional_driver);
        // }

        // generate selected extra cover list
        $selected_extra_cover = [];
        foreach ($input->insurance->extra_cover as $extra_cover) {
            array_push($selected_extra_cover, new ExtraCover([
                'extra_cover_code' => $extra_cover->code,
                'extra_cover_description' => $extra_cover->description,
                'premium' => floatval($extra_cover->amount),
                'sum_insured' => floatval($extra_cover->sum_insured) ?? 0,
                'cart_amount' => $extra_cover->cart_amount ?? 0,
                'cart_day' => $extra_cover->cart_day ?? 0,
            ]));
        }

        // Create a vehicle object with necessary fields only
        $input->vehicle = new Vehicle([
            'coverage' => 'Comprehensive',
            'engine_capacity' => $input->insurance_motor->engine_capacity,
            'expiry_date' => $input->insurance->expiry_date,
            'extra_attribute' => (object) [
                'body_type_code' => $extra_attribute->body_type_code,
                'body_type_description' => $extra_attribute->body_type_description,
                'chassis_number' => $extra_attribute->chassis_number,
                'cover_type' => $extra_attribute->cover_type,
                'engine_number' => $extra_attribute->engine_number,
                'liberty_model_code' => "",
                'make_code' => $extra_attribute->make_code,
                'model_code' => $extra_attribute->model_code,
                'seating_capacity' => $extra_attribute->seating_capacity,
                'vehicle_use_code' => $extra_attribute->vehicle_use_code,
                'vehicle_type_code' => $extra_attribute->vehicle_type_code,
            ],
            'inception_date' => $input->insurance->inception_date,
            'make' => $input->insurance_motor->make,
            'manufacture_year' => $input->insurance_motor->manufactured_year,
            'max_sum_insured' => formatNumber($input->insurance_motor->market_value),
            'min_sum_insured' => formatNumber($input->insurance_motor->market_value),
            'model' => $input->insurance_motor->model,
            'ncd_percentage' => floatval($input->insurance_motor->ncd_percentage),
            'nvic' => $input->insurance_motor->nvic,
            'sum_insured_type' => $input->insurance_motor->sum_insured_type,
            'sum_insured' => formatNumber($input->insurance_motor->market_value),
            'variant' => $input->insurance_motor->variant
        ]);

        // Include the necessary fields to the input for API call
        //$input->additional_driver = $additional_driver_list;
        $input->extra_cover = $selected_extra_cover;
        //Log::info("[API/Last/ExtraCover] Dzul Request Test: " . json_encode($input->extra_cover));
        //Log::info("[API/Last/Input] Dzul Request Test: " . json_encode($input));
        $premium_details = $this->getPremium($input);
        //Log::info("[API/Last/Premium] Dzul Request Test: " . json_encode($input));

        if (!$premium_details->status) {
            return $this->abort($premium_details->response);
        }

        // Include the necessary fields to the input for API call
        $input->premium_details = $premium_details->response;
        //Log::info("[API/Last/Premium] Dzul Response: " . json_encode($input));
 
        // Call issueCoverNote API
        $submission_result = $this->issueCoverNote($input);

        if (!$submission_result->status) {
            return $this->abort($submission_result->response);
        }

        return (object) ['status' => true, 'response' => $submission_result->response];
    }

    private function getVIXNCD(object $input) : ResponseData
    {
        $path = '/HowdenGetVehDetails';

        $timestamp = Carbon::now()->format(self::TIMESTAMP_FORMAT);
        $hash = $this->secret_key.$timestamp.$this->agent_code.'000'.$input->vehicle_number;
        $hashcode = $this->tariffSHA2($hash);

        $data = [
            'vehicle_number' => $input->vehicle_number,
            'agent_code' => $this->agent_code,
            'id_number' => preg_replace("/^(\d{6})(\d{2})(\d{4})$/", "$1-$2-$3", $input->id_number),
            'company_registration_number' => $input->company_registration_number ?? '',
            'state_code' => $this->getPostcode($input->postcode),
            'post_code' => $input->postcode,
            'user_id' => 'SYS'.$this->agent_code,
            'timestamp' => $timestamp,
            'company_code' => $this->company_code,
            'hash_code' => $hashcode,
            'class_code' => 'VP02',
            'cover_type' => 'CP'
        ];
        //Log::info("[API/GetVehicleDetails] Dzul Test: " . json_encode($data));
        // Generate XML from view
        $xml = view('backend.xml.lonpac.vehicle_details')->with($data)->render();

        // Call API
        $result = $this->cURL($path, $xml);

        if (!$result->status) {
            return $this->abort($result->response, $result->code);
        }

        //Dzul Test
        //Log::info("[API/GetVehicleDetails] Dzul Test: " . json_encode($result));
        // 1. Check Response Code
        $response_code = (string) $result->response->getInpuobjResponse->getInpuobjReturn->vixRespCode;
        if ($response_code != '000') {
            $message = (string) $result->response->getInpuobjResponse->getInpuobjReturn->vixRespMsg;
            return $this->abort($message, $response_code);
        }

        // Get ISM Market Value & Response Code
        $sum_insured = $min_sum_insured = $max_sum_insured = $min_market_value = $max_market_value = $min_agreed_value = $max_agreed_value = $ism_market_value = 0;
        $ism_response_code = $sum_insured_type = '';

        $min_market_value = formatNumber($result->response->getInpuobjResponse->getInpuobjReturn->lowestMarketValue, 0);
        $max_market_value = formatNumber($result->response->getInpuobjResponse->getInpuobjReturn->highestMarketValue, 0);
        $min_agreed_value = formatNumber($result->response->getInpuobjResponse->getInpuobjReturn->lowestAgreedValue, 0);
        $max_agreed_value = formatNumber($result->response->getInpuobjResponse->getInpuobjReturn->highestAgreedValue, 0);
        $ism_market_value = formatNumber($result->response->getInpuobjResponse->getInpuobjReturn->ismRecommendSI, 0);

        $sum_insured_type = 'Market Value';
        $sum_insured = formatNumber($result->response->getInpuobjResponse->getInpuobjReturn->sumInsured, 0);
        $min_sum_insured = $min_market_value;
        $max_sum_insured = $max_market_value;

        if ($min_agreed_value > 0) {
            $sum_insured_type = 'Agreed Value';
            $sum_insured = formatNumber($result->response->getInpuobjResponse->getInpuobjReturn->sumInsured, 0);
            $min_sum_insured = $min_agreed_value;
            $max_sum_insured = $max_agreed_value;
        }

        if($sum_insured == 0) {
            $sum_insured = $min_sum_insured = $max_sum_insured = $ism_market_value;
        }

        // Get Make & Model
        $nvic = (string) $result->response->getInpuobjResponse->getInpuobjReturn->nvic;
        $model = explode('|', $nvic);
        $response = (object) [
            'nvic' => $nvic,
            'sum_insured' => $sum_insured,
            'sum_insured_type' => $sum_insured_type,
            'chassis_number' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->chassisNumber,
            'coverage_code' => (int) $result->response->getInpuobjResponse->getInpuobjReturn->typeOfCover,
            'engine_number' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->engineNumber,
            'engine_capacity' => (int) $result->response->getInpuobjResponse->getInpuobjReturn->cubicCapacity,
            'expiry_date' => Carbon::createFromFormat('d/m/Y', (string) $result->response->getInpuobjResponse->getInpuobjReturn->expiryDate)->format('Y-m-d'),
            'make' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->makeDesc,
            'min_sum_insured' => $min_sum_insured,
            'max_sum_insured' => $max_sum_insured,
            'model' => $this->getModelDetails($model[0]),
            'make_code' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->make,
            'model_code' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->model,
            'ncd_response_code' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->ncdRespCode,
            'manufacture_year' => (int) $result->response->getInpuobjResponse->getInpuobjReturn->yearMake,
            //'cur_ncd_percentage' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->curNcdRate),
            'ncd_percentage' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->ncdRate),
            'vehicle_class_code' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->vehClass,
            'vehicle_use_code' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->vehUse,
            //'liberty_model_code' => $make_model_result->response->LIB_MODEL_CODE,
            'transaction_type' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->transactionType,
            //'variant' => implode(' ', [$make_model_result->response->VARIANT, $make_model_result->response->SERIES, $make_model_result->response->TRANSMISSION]),
            'variant' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->vsst,
            'transmission' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->transmission,
            'seating_capacity' => (int) $result->response->getInpuobjResponse->getInpuobjReturn->seat,
            'windscreen_franchise' => formatNumber($result->response->getInpuobjResponse->getInpuobjReturn->windscreenFranchiseCost, 0),
            'windscreen_local' => formatNumber($result->response->getInpuobjResponse->getInpuobjReturn->windscreenLocalCost, 0),
            'ins_code' => (int) $result->response->getInpuobjResponse->getInpuobjReturn->insCode,
            'pre_ins_code' => (int) $result->response->getInpuobjResponse->getInpuobjReturn->preInsCode,
            //'class_code' => $make_model_result->response->CLASS_CODE,
        ];

        return new ResponseData([
            'response' => $response
        ]);
    }

    private function getPremium(object $input) : ResponseData
    {
        $path = '/HowdenGetQuotation';
        Log::info("[API/GePremium] Dzul Request: " . json_encode($input));
        $request_id = Str::uuid();
        $quotation_number = 'HIB' . Carbon::now()->timestamp;

        $effective_time = '00:00:01';
        if(Carbon::parse($input->vehicle->inception_date)->lessThan(Carbon::today())) {
            $effective_time = date('H:i:s', strtotime('now'));
        }

        // Format list additional driver
        // if(isset($input->additional_driver)) {
        //     foreach ($input->additional_driver as $additional_driver) {
        //         $additional_driver->age = getAgeFromIC($additional_driver->id_number);
        //         $additional_driver->date_of_birth = formatDateFromIC($additional_driver->id_number);
        //         $additional_driver->gender = getGenderFromIC($additional_driver->id_number);
        //         $additional_driver->driving_exp = $additional_driver->age - 18 < 0 ? 0 : $additional_driver->age - 18;
        //     }
        // }

        // Format Extra Cover Code
        $formatted_extra_cover = [];
        if(isset($input->extra_cover)) {
            //Log::info("[API/GetNew] Dzul Request: " . json_encode($input->extra_cover));
            foreach($input->extra_cover as $item){
                if($item->extra_cover_code == "M51" && !empty($item->cart_day)){
                    $item->sum_insured = $item->cart_day * $item->cart_amount;
                }else if($item->extra_cover_code == "M51" && !empty($item->premium)){
                    $item->sum_insured = $item->premium / 0.1;
                }
            }
            $addcovdesc = array_column($input->extra_cover, 'extra_cover_description');
            $addcovdesc = implode('|', $addcovdesc);
            $addcovcode = array_column($input->extra_cover, 'extra_cover_code');
            $addcovcode = implode('|', $addcovcode);
            $addcovsi = array_column($input->extra_cover, 'sum_insured');
            $addcovsi = implode('|', $addcovsi);
            /* foreach ($input->extra_cover as $extra_cover) {
                $extra_cover_code = $extra_cover->extra_cover_code;
                $extra_cover->extra_cover_description = $this->getExtraBenefitDescription($extra_cover_code, $input->vehicle->engine_capacity);
                array_push($formatted_extra_cover, (object) [
                    'extra_cover_code' => $extra_cover_code,
                    'extra_cover_description' => $extra_cover->extra_cover_description,
                    'sum_insured' => $extra_cover->sum_insured ?? 0,
                    'unit' => $extra_cover->unit ?? 0
                ]);
            } */

            //Log::info("[API/GetExtraCover] Dzul Request Cover: " . json_encode($addcovdesc));
            //Log::info("[API/GetExtraCover] Dzul Request Cover: " . json_encode($addcovcode));
            //Log::info("[API/GetExtraCover] Dzul Request Cover: " . json_encode($addcovsi));
        }

        //$body_type = $this->getBodyTypeDetails($input->vehicle_body_type ?? $input->vehicle->extra_attribute->body_type_code);
        $timestamp = Carbon::now()->format(self::TIMESTAMP_FORMAT);
        $vehicle_age = Carbon::now()->year - $input->vehicle->manufacture_year;
        $hash = $this->secret_key.$timestamp.'000'.$quotation_number;
        $hashcode = $this->tariffSHA2($hash);
        $data = [
            'timestamp' => $timestamp, // The time when the request is made
            //'additional_driver' => $input->additional_driver ?? [],
            'hashcode'  => $hashcode,
            'company_code' => self::COMPANY_CODE,
            'agent_code' => $this->agent_code,
            'user_id' => 'SYS-'.$this->agent_code,
            'quotation_number' => $quotation_number,
            'effective_date' => Carbon::parse($input->vehicle->inception_date)->format('d/m/Y'),
            'effective_time' => $effective_time,
            'expiry_date' => Carbon::parse($input->vehicle->inception_date)->addYear()->subDay()->format('d/m/Y'),
            
            'pre_ins_code' => $input->vehicle->extra_attribute->pre_ins_code ?? 214,
            //'cur_ncd_percentage' => $input->vehicle->cur_ncd_percentage,

            'name' => $input->name ?? config('app.name'),
            'email' => $input->email ?? $input->insurance->holder->email_address,
            'id_number' => preg_replace("/^(\d{6})(\d{2})(\d{4})$/", "$1-$2-$3", $input->id_number),
            'date_of_birth' =>  Carbon::parse($input->dob)->format('d/m/Y') ?? '',
            'age' => getAgeFromIC($input->id_number),
            'gender' => $input->gender,
            'marital_status' => $this->maritalcode($input->marital_status),
            'occupation' => self::OCCUPATION,
            'occupation_code' => self::OCCUPATION_CODE,
            'address_1' => ($input->address_one ?? '1 Jalan 1'),
            'address_2' => $input->address_two ?? '',
            'address_3' => '',
            'region' => $input->city ?? '',
            'postcode' => $input->postcode,
            'phone_number' => isset($input->phone_number) ? substr_replace('0' . $input->phone_number, '-', 3, 0) : substr_replace("0123456789", '-', 3, 0),
            'vehicle_number' => $input->vehicle_number,
            'chassis_number' => $input->vehicle->extra_attribute->chassis_number,
            'engine_number' => $input->vehicle->extra_attribute->engine_number,

            'make' => str_pad($input->vehicle->extra_attribute->make_code, 2, '0', STR_PAD_LEFT),
            'model' => str_pad($input->vehicle->extra_attribute->model_code, 2, '0', STR_PAD_LEFT),
            'makemodeldesc' => $input->vehicle->make,

            'manufacture_year' => $input->vehicle->manufacture_year,
            'vehicle_capacity' => $input->vehicle->engine_capacity,
            'vehicle_capacity_code' => self::VEHICLE_CAPACITY_CODE,
            'seat_capacity' => $input->vehicle->extra_attribute->seating_capacity,

            'vehicle_age' => $vehicle_age,
            'ownership_type' => $input->ownership_type,
            'garage_code' => self::GARAGE_CODE,
            'safety_code' => self::SAFETY_CODE,
            'anti_theft' => self::ANTI_THEFT,

            'localimport' => 3, //kena check balik

            'nvic' => $input->nvic ?? $input->vehicle->nvic,

            //Additional Coverage Support
            'addcovdesc' => isset($input->extra_cover) ? $addcovdesc : '',
            'addcovcode' => isset($input->extra_cover) ? $addcovcode : '',
            'addcovsi' => isset($input->extra_cover) ? $addcovsi: '',
            'addcovunit' => '',


            'sum_insured' => $input->sum_insured ?? $input->vehicle->sum_insured,
            'ncd_percentage' => $input->vehicle->ncd_percentage,
            'gst_code' => self::GST_CODE,

            //'body_type_code' => $body_type->code,
            //'body_type_description' => $body_type->description,

            //'company_registration_number' => $input->company_registration_number,

            //'cover_code' => self::COVER_CODE,
            //'driving_experience' => $input->driving_experience,
            //'extra_benefit' => $formatted_extra_cover,



            //'id_number' => $input->id_number,
            //'liberty_model_code' => $input->vehicle->extra_attribute->liberty_model_code,



            //'purpose' => self::PURPOSE,

            //'request_id' => $request_id,


            //'signature' => $this->generateSignature($request_id),
            //'state_code' => $this->getStateCode($input->state),

            //'town_description' => $this->getStateName($input->state),
            //'use_code' => $input->vehicle->extra_attribute->vehicle_use_code,


            //'vehicle_type_code' => $input->vehicle->extra_attribute->vehicle_type_code,
        ];

        // Generate XML from view
        //Log::info("[API/GePremium] Dzul Request: " . json_encode($data));
        $xml = view('backend.xml.lonpac.premium')->with($data)->render();

        // Call API
        $result = $this->cURL($path, $xml);

        if (!$result->status) {
            return $this->abort($result->response);
        }

        // 1. Check Response Code
        //Log::info("[API/Response] Dzul Request Cover: " . json_encode($result));

        $response_code = (string) $result->code;
        if ($response_code != '200') {
            $message = (string) $result->response->getInpuobjResponse->getInpuobjReturn->errorMsg;

            return $this->abort($message);
        }

        // 2. Check Refer Risks
        /* $refer_code = (string) $result->response->getInpuobjResponse->getInpuobjReturn->refercode;
        if($refer_code != '') {
            $message = (string) $result->response->getInpuobjResponse->getInpuobjReturn->referdesc;

            return $this->abort(__('api.referred_risk', ['company' => $this->company_name, 'reason' => str_replace('^', ', ', $message)]), intval($refer_code));
        } */

        $response = (object) [
            'act_premium' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->actPremium),
            'commission_amount' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->commissionAmount),
            'excess' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->excess),
            'extra_benefit' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->addCovPrem,
            'fl_quote_number' => $quotation_number,
            'gross_due' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->grossDue),
            //'gross_due_2' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->grossdue2),
            'gross_premium' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->grossPremium),
            'loading_amount' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->loadingAmount),
            'loading_percentage' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->loadingPercent),
            'ncd_amount' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->NCDAmount),
            'ncd_percentage' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->NCDPercent),
            'net_due' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->netDue),
            //'net_due_2' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->netdue2),
            'net_premium' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->netPremium),
            //'refer_code' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->refercode,
            //'refer_description' => (string) $result->response->getInpuobjResponse->getInpuobjReturn->referdesc,
            'sst_amount' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->serviceTaxAmount),
            'sst_percentage' => formatNumber((int) $result->response->getInpuobjResponse->getInpuobjReturn->serviceTaxPercent, 0),
            'stamp_duty' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->stampDuty),
            'tariff_premium' => formatNumber((float) $result->response->getInpuobjResponse->getInpuobjReturn->tariffPremium),
        ];

        return new ResponseData([
            'response' => $response
        ]);
    }

    private function issueCoverNote(object $input) : ResponseData
    {
        $path = '/HowdenGenerateCoverNote';

        //$reference_number = Str::uuid();

        $quotation_number = $input->premium_details->fl_quote_number;
        $timestamp = Carbon::now()->format(self::TIMESTAMP_FORMAT);
        $hash = $this->secret_key.$timestamp.'000'.$quotation_number;
        $hashcode = $this->tariffSHA2($hash);

        // $effective_time = '00:00:01';
        // if(Carbon::parse($input->vehicle->inception_date)->lessThan(Carbon::today())) {
        //     $effective_time = Carbon::now()->format('H:i:s');
        // }
        $addcovcode = "";
        // Format Extra Cover Code
        if(isset($input->extra_cover)) {
            $addcovcode = array_column($input->extra_cover, 'extra_cover_code');
            $addcovcode = implode('|', $addcovcode);
        }
        //Payment Method from eghl CC(Credit Card), DD(Online Banking), ANY(eWallet)
        //getting EGHL log
        // Payment Gateway Charges
        // $eghl_log = EGHLLog::where('payment_id', 'LIKE', '%' . $input->insurance_code . '%')
        // ->where('txn_status', 0)
        // ->latest()
        // ->first();

        $sst_amount = $input->premium_details->sst_amount;
        $stamp_duty = $input->premium_details->stamp_duty;
        $net_premium = formatNumber($input->premium_details->net_premium + $sst_amount + $stamp_duty);

        $data = [
            'timestamp' => $timestamp,
            'hash_code'  => $hashcode,
            'agent_code' => $this->agent_code,
            'vehicle_number' => $input->insurance_motor->vehicle_number,
            'pdpatimestamp' => $timestamp,
            'quotationNo' => $quotation_number,
            'totdue' => $net_premium,
            'addcovcode' => $addcovcode,
        ];

        $xml = view('backend.xml.lonpac.cover_note_submission')->with($data)->render();

        $result = $this->cURL($path, $xml);

        // 1. check response code
        $response_code = (string) $result->response->getInputDataResponse->getInputDataReturn->respCode;
        if ($response_code != '000') {
            $message = (string) $result->response->getInputDataResponse->getInputDataReturn->respMsg;
            return $this->abort($message, $response_code);
        }

        $path = '/HowdenGeneratePolicy';
        $cover_note = (string) $result->response->getInputDataResponse->getInputDataReturn->CNNo;
        $timestamp = Carbon::now()->format(self::TIMESTAMP_FORMAT);
        $hash = $this->secret_key.$timestamp.'000'.$cover_note;
        $hashcode = $this->tariffSHA2($hash);

        $data = [
            'timestamp' => $timestamp,
            'hash_code'  => $hashcode,
            'agent_code' => $this->agent_code,
            'covernote' => $cover_note,
        ];

        $xml = view('backend.xml.lonpac.policy_generate')->with($data)->render();

        $result = $this->cURL($path, $xml);

        // 1. check response code
        $response_code = (string) $result->response->getInputDataResponse->getInputDataReturn->respCode;
        if ($response_code != '000' || $response_code != '004') {
            $message = (string) $result->response->getInputDataResponse->getInputDataReturn->respMsg;
            return $this->abort($message, $response_code);
        }

        $response = (object) [
            'policy_number' => (string) $result->response->getInputDataResponse->getInputDataReturn->PolicyNo
        ];

        return new ResponseData([
            'response' => $response
        ]);
    }

    private function cURL(string $path, string $xml, string $method = 'POST') : ResponseData
    {
        // Concatenate URL
        $url = $this->host . $path;

        // Check XML Errors
        libxml_use_internal_errors(true);

        // Construct API Request
        $request_options = [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/xml',
                'Accept' => 'application/xml',
                'soapAction' => ''
            ],
            'body' => $xml
        ];

        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $this->host,
            'path' => $path,
            'request_header' => json_encode($request_options['headers']),
            'request' => $xml,
        ]);

        $result = HttpClient::curl($method, $url, $request_options);

        // Update the Response
        APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => is_object($result->response) ? json_encode($result->response) : $result->response
            ]);

        if($result->status) {
            $response = simplexml_load_string($result->response);
            $response->registerXPathNamespace('res', 'http://schemas.xmlsoap.org/soap/envelope/');

            if($response === false) {
                return $this->abort(__('api.xml_error'));
            }

            $response = $response->xpath('res:Body')[0];
        } else {
            $message = '';
            if(empty($result->response)) {
                $message = __('api.empty_response', ['company' => $this->company_name]);
            } else {
                $message = 'An Error Encountered. ' . json_encode($result->response);
            }

            return $this->abort($message);
        }

        return new ResponseData([
            'status' => $result->status,
            'response' => $response
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

    private function generateSignature($request_id) : string
    {
        return base64_encode(hash('sha1', $request_id . $this->secret_key, true));
    }

    private function getStateCode($state_name) : string
    {
        $state_id = '';

        switch (strtolower($state_name)) {
            case 'johor': {
                    $state_id = 'J';
                    break;
                }
            case 'kedah': {
                    $state_id = 'K';
                    break;
                }
            case 'kelantan': {
                    $state_id = 'D';
                    break;
                }
            case 'melaka': {
                    $state_id = 'M';
                    break;
                }
            case 'negeri sembilan': {
                    $state_id = 'N';
                    break;
                }
            case 'pahang': {
                    $state_id = 'C';
                    break;
                }
            case 'perak': {
                    $state_id = 'A';
                    break;
                }
            case 'perlis': {
                    $state_id = 'R';
                    break;
                }
            case 'pulau pinang': {
                    $state_id = 'P';
                    break;
                }
            case 'sabah': {
                    $state_id = 'S';
                    break;
                }
            case 'sarawak': {
                    $state_id = 'Q';
                    break;
                }
            case 'selangor': {
                    $state_id = 'B';
                    break;
                }
            case 'terengganu': {
                    $state_id = 'T';
                    break;
                }
            case 'wilayah persekutuan kuala lumpur': {
                    $state_id = 'W';
                    break;
                }
            case 'wilayah persekutuan labuan': {
                    $state_id = 'L';
                    break;
                }
            case 'wilayah persekutuan putrajaya': {
                    $state_id = 'WP';
                    break;
                }
        }

        return $state_id;
    }

    private function getPostcode($postcode) : string
    {
        $data = Postcode::join('states', 'states.id', '=', 'postcodes.state_id')
                ->where('postcodes.postcode', $postcode)
                ->get(['states.name'])->first();
        return $data->name;
    }

    private function getExtraBenefitDescription($extra_cover_code, $engine_capacity = null) : string
    {
        $description = '';

        switch ($extra_cover_code) {
            case 'M01': {
                    $description = 'Legal Liability to Passengers';
                    break;
                }
            case 'M11': {
                    $description = 'Legal Liability to passengers for Negligent Acts (LLAN)';
                    break;
                }
            case 'M02': {
                $description = 'Windscreen';
                break;
            }
            case 'M03': {
                    $description = 'Strike Riot and Civil Commotion';
                    break;
                }
            case 'M17': {
                    $description = 'Special Perlis (Flood, Earthquake, Ladslide, etc)';
                    break;
                }
            case 'M62': {
                    $description = 'e-Hailing';
                    break;
                }
            case 'M51': {
                    $description = 'Compensation for Asseessed Repair Time (CART)';
                    break;
                }
            case 'M68': {
                $description = 'Betterment Buyback';
                break;
            }
            case 'M65': {
                $description = 'Replacement Cost of Car Keys';
                break;
            }
            case 'M66': {
                $description = 'Cleaning Cost of Vehicle';
                break;
            }
            case 'M67': {
                $description = 'Complete Respray of Car';
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
                case 'M01': { // Legal Liability to Passengers
                        $sequence = 1;

                        break;
                    }
                case 'M11': { // Legal Liability to passengers for Negligent Acts (LLAN)
                        $sequence = 2;

                        break;
                    }
                case 'M02': { // Windscreen
                        $sequence = 3;

                        break;
                    }
                case 'M03': { // Strike Riot & Civil Commotion
                        $sequence = 4;

                        break;
                    }
                case 'M17': { // Special Perlis (Flood, Earthquake, Ladslide, etc)
                        $sequence = 5;

                        break;
                    }
                case 'M62': { // e-Hailing
                        $sequence = 6;

                        break;
                    }
                case 'M51': { // Compensation for Asseessed Repair Time (CART)
                    $sequence = 7;

                    break;
                }
                case 'M68': { // Betterment Buyback
                    $sequence = 8;

                    break;
                }
                case 'M65': { // Replacement Cost of Car Keys
                    $sequence = 9;

                    break;
                }
                case 'M66': { // Cleaning Cost of Vehicle
                    $sequence = 10;

                    break;
                }
                case 'M67': { // Complete Respray of Car
                    $sequence = 11;

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

    private function tariffSHA2($inString) {
        $digest = hash("sha256", $inString, true);
        $hascode = base64_encode($digest);
        return $hascode;
    }

    private function maritalcode($code){
        if($code == "S"){
            $code = "0";
        }else if($code == "M"){
            $code = "1";
        }else if($code == "D"){
            $code = "2";
        }else if($code == "O"){
            $code = "3";
        }
        return $code;
    }

    private function getModelDetails(string $nvic)
    {
        $model_details = null;

        try {
            $json = File::get(storage_path('Motor/ism_model.json'));
            $model_listing = json_decode($json, true);

            foreach ($model_listing['modelcode'] as $model) {
                if (trim($model['NVIC']) == $nvic) {
                    $model_details = trim($model['MINORDESC']);
                }
            }
        } catch (FileNotFoundException $ex) {
            return $this->abort('Model mapping file not found!');
        }

        return $model_details;
    }
}
