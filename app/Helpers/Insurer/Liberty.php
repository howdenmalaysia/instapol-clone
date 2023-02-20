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

class Liberty implements InsurerLibraryInterface
{
    private string $host;
    private string $agent_code;
    private string $secret_key;
    private string $company_id;
    private string $company_name;

    // Settings
    private const COMPANY_CODE = '30';
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 500000;
    private const TIMESTAMP_FORMAT = 'Y-m-d H:i:s.u';
    private const EXTRA_BENEFIT_LIST = [
        '101', '105', '111', '112', '25',
        '40', '57', '72', '89', '97', '97A',
        '10',
    ];
    private const CART_DAY_LIST = [7, 14];
    private const ANTI_THEFT = '03'; // Without M.Device - Factory Fitted Alarm
    private const COVER_CODE = 'COMP'; // Comprehensive
    private const GARAGE_CODE = '3'; // Locked Compound
    private const GST_CODE = 'SST';
    private const OCCUPATION = '11'; // Others (In Group)
    private const PIAM_DRIVER = '03'; // All Drivers
    private const PURPOSE = 'NB'; // New Business
    private const SAFETY_CODE = '06'; // ABS & Airbags 2
    private const VEHICLE_CAPACITY_CODE = 'CC';

    public function __construct(int $insurer_id, string $insurer_name)
    {
        $this->company_id = $insurer_id;
        $this->company_name = $insurer_name;

        $this->host = config('insurer.config.liberty.host');
        $this->agent_code = config('insurer.config.liberty.agent_code');
        $this->secret_key = config('insurer.config.liberty.secret_key');
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
            'company_registration_number' => $company_registration_number
        ];

        $vehicle_vix = $this->getVIXNCD($data);

        if (!$vehicle_vix->status) {
            return $this->abort($vehicle_vix->response, $vehicle_vix->code);
        }

        // Get coverage dates
        $inception_date = Carbon::parse($vehicle_vix->response->current_policy_expiry_date)->addDay();
        $expiry_date = Carbon::parse($inception_date)->addYear()->subDay()->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');

        // Check Inception Date
        if ($inception_date < $today) {
            $gap_in_cover = abs(Carbon::today()->diffInDays($inception_date));
            // Liberty Doesn't Allow Renewal for Expired Policy
            if ($gap_in_cover > 0) {
                // return $this->abort(__('api.gap_in_cover', ['days' => $gap_in_cover]), config('setting.response_codes.gap_in_cover'));
            }

            $inception_date = Carbon::parse($today);
            $expiry_date = Carbon::today()->addYear()->subDay()->format('Y-m-d');
        } else {
            // Check 2 Months Before
            if (Carbon::parse($today)->addMonths(2)->lessThan($inception_date)) {
                return $this->abort(__('api.earlier_renewal'), config('setting.response_codes.earlier_renewal'));
            }
        }

        // Check Sum Insured
        $sum_insured = formatNumber($vehicle_vix->response->sum_insured, 0);
        if ($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(
                __('api.sum_insured_referred_between', ['min_sum_insured' => self::MIN_SUM_INSURED, 'max_sum_insured' => self::MAX_SUM_INSURED]),
                config('setting.response_codes.sum_insured_referred')
            );
        }

        $variants = [];
        array_push($variants, new VariantData([
            'nvic' => (string) $vehicle_vix->response->nvic,
            'sum_insured' => floatval($vehicle_vix->response->sum_insured),
            'variant' => $vehicle_vix->response->variant,
        ]));

        return (object) [
            'status' => true,
            'response' => new VIXNCDResponse([
                'body_type_code' => 01,
                'body_type_description' => 'SALOON',
                'chassis_number' => (string) $vehicle_vix->response->chassis_number,
                'class_code' => $vehicle_vix->response->class_code,
                'coverage' => 'Comprehensive',
                'cover_type' => (string) $vehicle_vix->response->coverage_code,
                'engine_capacity' => (int) $vehicle_vix->response->engine_capacity,
                'engine_number' => (string) $vehicle_vix->response->engine_number,
                'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
                'inception_date' => $inception_date->format('d M Y'),
                'liberty_model_code' => $vehicle_vix->response->liberty_model_code,
                'make' => $vehicle_vix->response->make,
                'manufacture_year' => $vehicle_vix->response->manufacture_year,
                'make_code' => intval($vehicle_vix->response->make_code),
                'max_sum_insured' => floatval($vehicle_vix->response->max_sum_insured),
                'min_sum_insured' => floatval($vehicle_vix->response->min_sum_insured),
                'model' => $vehicle_vix->response->model,
                'model_code' => intval($vehicle_vix->response->model_code),
                'ncd_percentage' => $vehicle_vix->response->ncd_percentage,
                'seating_capacity' => $vehicle_vix->response->seating_capacity,
                'sum_insured' => floatval($sum_insured),
                'sum_insured_type' => $vehicle_vix->response->sum_insured_type,
                'variants' => $variants,
                'vehicle_number' => $input->vehicle_number,
                'vehicle_use_code' => $vehicle_vix->response->vehicle_use_code,
                'vehicle_type_code' => $vehicle_vix->response->vehicle_type_code,
            ])
        ];
    }

    public function premiumDetails(object $input, $full_quote = false) : object
    {
        $vehicle = $input->vehicle ?? null;
        $basic_premium = $ncd_percentage = $ncd_amount = $total_benefit_amount = $gross_premium = $sst_amount = $sst_percent = $stamp_duty = $excess_amount = $total_payable = $net_premium = 0;

        $id_number = $company_registration_number = $ownership_type = $date_of_birth = $gender = $marital_status = '';
        $driving_experience = 0;
        switch($input->id_type) {
            case config('setting.id_type.nric_no'): {
                $id_number = $input->id_number;
                $ownership_type = 'I'; // Individual
                $date_of_birth = formatDateFromIC($input->id_number);
                $driving_experience = getAgeFromIC($input->id_number) - 18 < 0 ? 18 : getAgeFromIC($input->id_number) - 18;
                $gender = $input->gender;
                $marital_status = $input->marital_status;

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
                    'liberty_model_code' => $vehicle_detail->response->liberty_model_code,
                    'make_code' => $vehicle_detail->response->make_code,
                    'model_code' => $vehicle_detail->response->model_code,
                    'seating_capacity' => $vehicle_detail->response->seating_capacity,
                    'vehicle_use_code' => $vehicle_detail->response->vehicle_use_code,
                    'vehicle_type_code' => $vehicle_detail->response->vehicle_type_code,
                ],
                'inception_date' => Carbon::createFromFormat('d M Y', $vehicle_detail->response->inception_date)->format('Y-m-d'),
                'make' =>  $vehicle_detail->response->make,
                'manufacture_year' => $vehicle_detail->response->manufacture_year,
                'max_sum_insured' => $vehicle_detail->response->max_sum_insured,
                'min_sum_insured' => $vehicle_detail->response->min_sum_insured,
                'model' => $vehicle_detail->response->model,
                'ncd_percentage' => $vehicle_detail->response->ncd_percentage,
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
            foreach (self::EXTRA_BENEFIT_LIST as $_extra_cover_code) {
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
                    case '112': { // CART
                            // Get Cart Days & its Amount Per Day
                            $cart_list = [];

                            foreach (self::CART_DAY_LIST as $_cart_day) {
                                $cart_amount_list = [];

                                if($_cart_day == '7') {
                                    array_push($cart_amount_list, 100);
                                } else {
                                    array_push($cart_amount_list, 50);
                                }

                                array_push($cart_list, new CartList([
                                    'cart_day' => $_cart_day,
                                    'cart_amount_list' => $cart_amount_list
                                ]));
                            }

                            $extra_cover->cart_list = $cart_list;

                            // Get The Lowest Cart Day & Amount Per Day to Get Premium
                            $_cart_day = $cart_list[0]->cart_day;
                            $_cart_amount = $cart_list[0]->cart_amount_list[0];

                            break;
                        }
                    case '89': { // Windscreen
                            // generate options from 500 to 10,000
                            $option_list = new OptionList([
                                'name' => 'sum_insured',
                                'description' => 'Sum Insured Amount',
                                'values' => generateExtraCoverSumInsured(500, 10000, 1000),
                                'any_value' => true,
                                'increment' => 100
                            ]);

                            $extra_cover->option_list = $option_list;

                            // Default to RM1,000 to Get Premium
                            $_sum_insured_amount = $option_list->values[1];

                            break;
                        }
                    case '97': { // Multimedia Player Or Other Accessories
                            // Generate Options from 1,000 to 5,000
                            $option_list = new OptionList([
                                'name' => 'sum_insured',
                                'description' => 'Sum Insured Amount',
                                'values' => generateExtraCoverSumInsured(1000, 5000, 1000),
                                'any_value' => true,
                                'increment' => 1000
                            ]);

                            $extra_cover->option_list = $option_list;

                            // Default to RM1,000 to Get Premium
                            $_sum_insured_amount = $option_list->values[0];

                            break;
                        }
                    case '97A': { // Gas Conversion Kit and Tank
                            // Generate Options from 1,000 to 10,000
                            $option_list = (object) [
                                'name' => 'sum_insured',
                                'description' => 'Sum Insured Amount',
                                'values' => generateExtraCoverSumInsured(1000, 10000, 1000),
                                'any_value' => true,
                                'increment' => 1000
                            ];

                            $extra_cover->option_list = $option_list;

                            // Default to RM1,000 to Get Premium
                            $_sum_insured_amount = $option_list->values[0];

                            break;
                        }
                    case '72': // Legal Liability Of Passengers
                    case '25': // Strike, Riot, Civil Commotion
                    case '57': // Inclusion of Special Perils
                    case '40': { // Legal Liability To Passengers
                            $_sum_insured_amount = $vehicle->sum_insured;
                            break;
                        }
                }

                if(!empty($_sum_insured_amount)) {
                    $extra_cover->sum_insured = $_sum_insured_amount;
                } else if(!empty($_cart_day) && !empty($_cart_amount)) {
                    $extra_cover->cart_day = $_cart_day;
                    $extra_cover->cart_amount = $_cart_amount;
                }

                array_push($extra_cover_list, $extra_cover);
            }

            // Include Extra Covers to Get Premium
            $input->extra_cover = $extra_cover_list;
        }

        // Add in Additional Named Driver if applicable
        if (isset($input->additional_driver)) {
            if (count($input->additional_driver) > 0 && array_search('07', array_column($input->extra_cover, 'extra_cover_code')) == false) {
                array_push($input->extra_cover, new ExtraCover([
                    'extra_cover_code' => '07',
                    'extra_cover_description' => $this->getExtraBenefitDescription('07', $vehicle->engine_capacity),
                    'sum_insured' => 0,
                    'unit' => count($input->additional_driver)
                ]));
            } else {
                $index = array_search('07', array_column($input->extra_cover, 'extra_cover_code'));

                if(!empty($index)) {
                    $input->extra_cover[$index]->unit = count($input->additional_driver);
                }
            }
        }

        $data = (object) [
            'additional_driver' => $input->additional_driver,
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
        ];

        $motor_premium = $this->getPremium($data);

        if (!$motor_premium->status) {
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

        if(isset($motor_premium->response->extra_benefit->item)) {
            foreach($motor_premium->response->extra_benefit->item as $item) {
                $total_benefit_amount += formatNumber($item->benpremium);

                foreach($input->extra_cover as $extra_cover) {
                    if((strpos((string) $item->bencode, $extra_cover->extra_cover_code) !== false && strpos((string) $item->bencode, '40') !== false && strpos((string) $extra_cover->extra_cover_code, '40') !== false) ||
                    ($item->bencode == 'CART' && $extra_cover->extra_cover_code == '112') ||
                    ($item->bencode == $extra_cover->extra_cover_code)) {
                        $extra_cover->premium = formatNumber($item->benpremium);
                        $extra_cover->selected = floatval($item->benpremium) == 0;
                    }
                }
            }
        }

        $response = new PremiumResponse([
            'basic_premium' => formatNumber(($motor_premium->response->gross_premium + $motor_premium->response->ncd_amount) - $total_benefit_amount),
            'excess_amount' => formatNumber($motor_premium->response->excess),
            'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
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
            'fl_quote_number' => $motor_premium->response->fl_quote_number,
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
            'additional_driver' => $input->additional_driver,
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
        $additional_driver_list = [];
        foreach ($input->insurance_motor->driver as $driver) {
            $additional_driver = (object) [
                'age' => getAgeFromIC($driver->id_number),
                'birth_date' => formatDateFromIC($driver->id_number),
                'driving_exp' => getAgeFromIC($driver->id_number) - 18 < 0 ? 0 : getAgeFromIC($driver->id_number) - 18,
                'gender' => getGenderFromIC($driver->id_number),
                'id_number' => $driver->id_number,
                'name' => $driver->name,
                'relationship' => $driver->relationship_id,
            ];

            array_push($additional_driver_list, $additional_driver);
        }

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
                'liberty_model_code' => $extra_attribute->liberty_model_code,
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
        $input->additional_driver = $additional_driver_list;
        $input->extra_cover = $selected_extra_cover;

        $premium_details = $this->getPremium($input);

        if (!$premium_details->status) {
            return $this->abort($premium_details->response);
        }

        // Include the necessary fields to the input for API call
        $input->premium_details = $premium_details->response;

        // Call issueCoverNote API
        $submission_result = $this->issueCoverNote($input);

        if (!$submission_result->status) {
            return $this->abort($submission_result->response);
        }

        return (object) ['status' => true, 'response' => $submission_result->response];
    }

    private function getVIXNCD(object $input) : ResponseData
    {
        $path = '/GetVIXNCD';

        $request_id = Str::uuid();

        $data = [
            'request_id' => $request_id,
            'signature' => $this->generateSignature($request_id),
            'company_code' => self::COMPANY_CODE,
            'agent_code' => $this->agent_code,
            'vehicle_number' => $input->vehicle_number,
            'id_number' => $input->id_number,
            'company_registration_number' => $input->company_registration_number
        ];

        // Generate XML from view
        $xml = view('backend.xml.liberty.vehicle_details')->with($data)->render();

        // Call API
        $result = $this->cURL($path, $xml);

        if (!$result->status) {
            return $this->abort($result->response, $result->code);
        }

        // 1. Check Response Code
        $response_code = (string) $result->response->getVixNcdReqReturn->respcode;
        if ($response_code != '1') {
            $message = (string) $result->response->getVixNcdReqReturn->respdesc;

            if($message == 'N001') {
                return $this->abort('NCD Not Match with ISM. (Error Code: ' . $message . ')', 482);
            } elseif($message == '001') {
                return $this->abort('Data Not Found. (Error Code: ' . $message . ')', 481);
            } else {
                return $this->abort($message, $response_code);
            }
        }

        // Get ISM Market Value & Response Code
        $sum_insured = $min_sum_insured = $max_sum_insured = $min_market_value = $max_market_value = $min_agreed_value = $max_agreed_value = $ism_market_value = 0;
        $ism_response_code = $sum_insured_type = '';

        foreach ($result->response->getVixNcdReqReturn->arrResExtraParam->item as $_item) {
            if ($_item->indicator == 'ismsubrespcode') {
                $ism_response_code = (string) $_item->value;
            } else if ($_item->indicator == 'minmarketvalue') {
                $min_market_value = formatNumber($_item->value, 0);
            } else if ($_item->indicator == 'maxmarketvalue') {
                $max_market_value = formatNumber($_item->value, 0);
            } else if ($_item->indicator == 'minagreedvalue') {
                $min_agreed_value = formatNumber($_item->value, 0);
            } else if ($_item->indicator == 'maxagreedvalue') {
                $max_agreed_value = formatNumber($_item->value, 0);
            } else if($_item->indicator == 'ismmarketvalue') {
                $ism_market_value = formatNumber($_item->value, 0);
            }
        }

        $sum_insured_type = 'Market Value';
        $sum_insured = $min_market_value;
        $min_sum_insured = $min_market_value;
        $max_sum_insured = $max_market_value;

        if ($min_agreed_value > 0) {
            $sum_insured_type = 'Agreed Value';
            $sum_insured = $min_agreed_value;
            $min_sum_insured = $min_agreed_value;
            $max_sum_insured = $max_agreed_value;
        }

        if($sum_insured == 0) {
            $sum_insured = $min_sum_insured = $max_sum_insured = $ism_market_value;
        }

        // Get Make & Model
        $nvic = (string) $result->response->getVixNcdReqReturn->NVIC;

        if (!empty($nvic)) {
            $make_model_result = $this->getMakeModel($nvic);

            if (!$make_model_result->status) {
                return $this->abort($make_model_result->response);
            }
        } else {
            return $this->abort(__('api.empty_nvic', ['insurer_id' => $this->company_id]));
        }

        $response = (object) [
            'nvic' => $nvic,
            'sum_insured' => $sum_insured,
            'sum_insured_type' => $sum_insured_type,
            'ism_response_code' => $ism_response_code,
            'chassis_number' => (string) $result->response->getVixNcdReqReturn->chassisno,
            'coverage_code' => (string) $result->response->getVixNcdReqReturn->covercode,
            'engine_number' => (string) $result->response->getVixNcdReqReturn->engineno,
            'current_policy_expiry_date' => Carbon::createFromFormat('d/m/Y', (string) $result->response->getVixNcdReqReturn->expirydate)->format('Y-m-d'),
            'make' => $make_model_result->response->MAKE,
            'min_sum_insured' => $min_sum_insured,
            'max_sum_insured' => $max_sum_insured,
            'model' => $make_model_result->response->FAMILY,
            'make_code' => (string) $result->response->getVixNcdReqReturn->ismmakecode,
            'model_code' => (string) $result->response->getVixNcdReqReturn->ismmodelcode,
            'ncd_response_code' => (string) $result->response->getVixNcdReqReturn->ismncdrespcode,
            'manufacture_year' => (int) $result->response->getVixNcdReqReturn->makeyear,
            'ncd_percentage' => formatNumber((float) $result->response->getVixNcdReqReturn->ncdperc),
            'engine_capacity' => (int) $result->response->getVixNcdReqReturn->vehcapacity,
            'vehicle_type_code' => (string) $result->response->getVixNcdReqReturn->vehtypecode,
            'vehicle_use_code' => (string) $result->response->getVixNcdReqReturn->vehusecode,
            'liberty_model_code' => $make_model_result->response->LIB_MODEL_CODE,
            'variant' => implode(' ', [$make_model_result->response->VARIANT, $make_model_result->response->SERIES, $make_model_result->response->TRANSMISSION]),
            'seating_capacity' => (int) $make_model_result->response->SEAT,
            'class_code' => $make_model_result->response->CLASS_CODE,
        ];

        return new ResponseData([
            'response' => $response
        ]);
    }

    private function getPremium(object $input) : ResponseData
    {
        $path = '/GetPremium';

        $request_id = Str::uuid();
        $quotation_number = 'HIB' . Carbon::now()->timestamp;

        $effective_time = '00:00:01';
        if(Carbon::parse($input->vehicle->inception_date)->lessThan(Carbon::today())) {
            $effective_time = date('H:i:s', strtotime('now'));
        }

        // Format list additional driver
        if(isset($input->additional_driver)) {
            foreach ($input->additional_driver as $additional_driver) {
                $additional_driver->age = getAgeFromIC($additional_driver->id_number);
                $additional_driver->date_of_birth = formatDateFromIC($additional_driver->id_number);
                $additional_driver->gender = getGenderFromIC($additional_driver->id_number);
                $additional_driver->driving_exp = $additional_driver->age - 18 < 0 ? 0 : $additional_driver->age - 18;
            }
        }

        // Format Extra Cover Code
        $formatted_extra_cover = [];
        if(isset($input->extra_cover)) {
            foreach ($input->extra_cover as $extra_cover) {
                $extra_cover_code = $extra_cover->extra_cover_code;
                $extra_cover->extra_cover_description = $this->getExtraBenefitDescription($extra_cover_code, $input->vehicle->engine_capacity);

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
                    'extra_cover_code' => $extra_cover_code,
                    'extra_cover_description' => $extra_cover->extra_cover_description,
                    'sum_insured' => $extra_cover->sum_insured ?? 0,
                    'unit' => $extra_cover->unit ?? 0
                ]);
            }
        }

        $body_type = $this->getBodyTypeDetails($input->vehicle_body_type ?? $input->vehicle->extra_attribute->body_type_code);

        $data = [
            'additional_driver' => $input->additional_driver ?? [],
            'address_1' => ($input->address_one ?? '1 Jalan 1'),
            'address_2' => isset($input->address_two) ? (empty($input->address_two) ? $input->city . ', ' . $input->state : $input->address_two) : '',
            'address_3' => isset($input->address_two) ? (empty($input->address_two) ? '' : $input->city . ', ' . $input->state) : '',
            'agent_code' => $this->agent_code,
            'anti_theft' => self::ANTI_THEFT,
            'body_type_code' => $body_type->code,
            'body_type_description' => $body_type->description,
            'chassis_number' => $input->vehicle->extra_attribute->chassis_number,
            'company_registration_number' => $input->company_registration_number,
            'company_code' => self::COMPANY_CODE,
            'cover_code' => self::COVER_CODE,
            'date_of_birth' => $input->dob ?? '',
            'driving_experience' => $input->driving_experience,
            'effective_date' => Carbon::parse($input->vehicle->inception_date)->format('Y-m-d'),
            'effective_time' => $effective_time,
            'expiry_date' => Carbon::parse($input->vehicle->inception_date)->addYear()->subDay()->format('Y-m-d'),
            'extra_benefit' => $formatted_extra_cover,
            'engine_number' => $input->vehicle->extra_attribute->engine_number,
            'garage_code' => self::GARAGE_CODE,
            'gender' => $input->gender,
            'gst_code' => self::GST_CODE,
            'id_number' => $input->id_number,
            'make_code' => $input->vehicle->extra_attribute->make_code,
            'liberty_model_code' => $input->vehicle->extra_attribute->liberty_model_code,
            'manufacture_year' => $input->vehicle->manufacture_year,
            'marital_status' => $input->marital_status,
            'name' => $input->name ?? config('app.name'),
            'ncd_percentage' => $input->vehicle->ncd_percentage,
            'occupation' => self::OCCUPATION,
            'ownership_type' => $input->ownership_type,
            'phone_number' => isset($input->phone_number) ? '60' . $input->phone_number : '60123456789',
            'piam_driver' => self::PIAM_DRIVER,
            'postcode' => $input->postcode,
            'purpose' => self::PURPOSE,
            'quotation_number' => $quotation_number,
            'region' => strtoupper(substr($input->region, 0, 1)),
            'request_id' => $request_id,
            'safety_code' => self::SAFETY_CODE,
            'seat_capacity' => $input->vehicle->extra_attribute->seating_capacity,
            'signature' => $this->generateSignature($request_id),
            'state_code' => $this->getStateCode($input->state),
            'sum_insured' => $input->sum_insured ?? $input->vehicle->sum_insured,
            'timestamp' => Carbon::now()->format(self::TIMESTAMP_FORMAT), // The time when the request is made
            'town_description' => $this->getStateName($input->state),
            'use_code' => $input->vehicle->extra_attribute->vehicle_use_code,
            'vehicle_capacity' => $input->vehicle->engine_capacity,
            'vehicle_capacity_code' => self::VEHICLE_CAPACITY_CODE,
            'vehicle_number' => $input->vehicle_number,
            'vehicle_type_code' => $input->vehicle->extra_attribute->vehicle_type_code,
        ];

        // Generate XML from view
        $xml = view('backend.xml.liberty.premium')->with($data)->render();

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

            return $this->abort(__('api.referred_risk', ['company' => $this->company_name, 'reason' => str_replace('^', ', ', $message)]), intval($refer_code));
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

    private function issueCoverNote(object $input) : ResponseData
    {
        $path = '/IssueCoverNote';

        $reference_number = Str::uuid();

        $effective_time = '00:00:01';
        if(Carbon::parse($input->vehicle->inception_date)->lessThan(Carbon::today())) {
            $effective_time = Carbon::now()->format('H:i:s');
        }

        // Format Extra Cover Code
        $formatted_extra_cover = [];
        if(isset($input->extra_cover)) {
            foreach ($input->extra_cover as $extra_cover) {
                $extra_cover_code = $extra_cover->extra_cover_code;
                $extra_cover->extra_cover_description = $this->getExtraBenefitDescription($extra_cover_code, $input->vehicle->engine_capacity);

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
                    $extra_cover->sum_insured = $input->insurance_motor->sum_insured;
                }

                array_push($formatted_extra_cover, new ExtraCover([
                    'extra_cover_code' => $extra_cover_code,
                    'extra_cover_description' => $extra_cover->extra_cover_description,
                    'sum_insured' => $extra_cover->sum_insured ?? 0,
                    'unit' => $extra_cover->unit ?? 0,
                    'premium' => $extra_cover->premium,
                    'cart_amount' => $extra_cover->cart_amount ?? 0,
                    'cart_day' => $extra_cover->cart_day ?? 0,
                ]));
            }
        }

        $data = [
            'act_premium' => $input->premium_details->act_premium,
            'additional_driver' => $input->additional_driver,
            'address_1' => $input->address_one,
            'address_2' => empty($input->address_two) ? $input->city . ', ' . $input->state : $input->address_two,
            'address_3' => empty($input->address_two) ? '' : $input->city . ', ' . $input->state,
            'agent_code' => $this->agent_code,
            'anti_theft' => self::ANTI_THEFT,
            'chassis_number' => $input->vehicle->extra_attribute->chassis_number,
            'commission_percentage' => 10,
            'company_code' => self::COMPANY_CODE,
            'company_registration_code' => $input->company_registration_code,
            'cover_code' => self::COVER_CODE,
            'date_of_birth' => $input->dob,
            'domain' => $this->host,
            'driving_experience' => $input->driving_experience,
            'effective_date' => Carbon::parse($input->insurance->inception_date)->format('Y-m-d'),
            'effective_time' => $effective_time,
            'email' => $input->insurance->holder->email,
            'engine_number' => $input->vehicle->extra_attribute->engine_number,
            'excess' => formatNumber($input->insurance_motor->excess_amount),
            'expiry_date' => Carbon::parse($input->insurance->expiry_date)->format('Y-m-d'),
            'extra_benefit' => $formatted_extra_cover,
            'fl_quote_number' => $input->premium_details->fl_quote_number,
            'garage_code' => self::GARAGE_CODE,
            'gender' => $input->gender,
            'gross_due' => $input->premium_details->gross_due,
            'gross_due_2' => $input->premium_details->gross_due_2,
            'gross_premium' => $input->premium_details->gross_premium,
            'phone_number' => $input->phone_number,
            'id_number' => $input->id_number,
            'loading_amount' => $input->premium_details->loading_amount,
            'loading_percentage' => $input->premium_details->loading_percentage,
            'make_code' => $input->vehicle->extra_attribute->make_code,
            'manufacture_year' => $input->insurance_motor->manufacture_year,
            'marital_status' => $input->marital_status,
            'model_code' => $input->vehicle->extra_attribute->liberty_model_code,
            'name' => $input->name,
            'ncd_amount' => formatNumber($input->insurance_motor->ncd),
            'ncd_percentage' => $input->insurance_motor->ncd_percentage,
            'net_due' => $input->premium_details->net_due,
            'net_due_2' => $input->premium_details->net_due_2,
            'net_premium' => $input->premium_details->net_premium,
            'occupation_code' => self::OCCUPATION,
            'ownership_type' => $input->ownership_type,
            'path' => $path,
            'payment_amount' => 0,
            'piam_driver' => self::PIAM_DRIVER,
            'postcode' => $input->insurance->address->postcode,
            'previous_ncd_percentage' => getPreVehicleNCD($input->insurance_motor->ncd_percentage),
            'purpose' => self::PURPOSE,
            'region' => strtoupper(substr($input->region, 0, 1)),
            'request_id' => $reference_number,
            'safety_code' => self::SAFETY_CODE,
            'signature' => $this->generateSignature($reference_number),
            'sum_insured' => formatNumber($input->insurance_motor->sum_insured),
            'sst_amount' => $input->premium_details->sst_amount,
            'sst_percentage' => $input->premium_details->sst_percentage,
            'stamp_duty' => formatNumber($input->premium_details->stamp_duty),
            'tariff_premium' => $input->premium_details->tariff_premium,
            'vehicle_body_code' => $input->vehicle->extra_attribute->body_type_code,
            'vehicle_body_description' => $input->vehicle->extra_attribute->body_type_description,
            'vehicle_capacity' => $input->insurance_motor->engine_capacity,
            'vehicle_capacity_code' => self::VEHICLE_CAPACITY_CODE,
            'vehicle_number' => $input->insurance_motor->vehicle_number,
            'vehicle_use_code' => $input->vehicle->extra_attribute->vehicle_use_code,
            'vehicle_type_code' => $input->vehicle->extra_attribute->vehicle_type_code,
        ];

        $xml = view('xml.Motor.Liberty.issue_cover_note')->with($data)->render();

        $result = $this->cURL($path, $xml);

        if (!$result->status) {
            return $this->abort($result->response);
        }

        // 1. check response code
        $response_code = (string) $result->response->issueCoverNoteReqReturn->respcode;
        if ($response_code != '1') {
            $message = (string) $result->response->issueCoverNoteReqReturn->respdesc;

            return $this->abort($message, $response_code);
        }

        $response = (object) [
            'policy_number' => (string) $result->response->issueCoverNoteReqReturn->covernoteno
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

    // Mapping
    private function getMakeModel($nvic) : ResponseData
    {
        $method = 'POST';
        $domain = 'https://www.libertyinsurance.com.my';
        $path = '/AgencyPortal/getNvicABI.php';

        // initialize api request
        $request_options = [
            'timeout' => 60,
            'form_params' => [
                'NVIC' => $nvic,
                'ACCESS_CODE' => 'LibertyInsuranceBerhadGroundFloor,MenaraLiberty1008,JalanSultanIsmail50250KualaLumpur@online'
            ]
        ];

        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $domain,
            'path' => $path,
            'request_header' => json_encode([]),
            'request' => json_encode($request_options['form_params']),
        ]);

        // API call
        $result = HttpClient::curl($method, $domain . $path, $request_options);

        // Update Log with Result
        APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => is_object($result->response) ? json_encode($result->response) : $result->response
            ]);

        if ($result->status) {
            $json = json_decode($result->response);

            if (empty($json)) {
                $message = !empty($result->response) ? $result->response : __('api.empty_response', ['company' => $this->company_name]);

                return $this->abort($message);
            }

            $result->response = $json[0];
        } else {
            $message = !empty($result->response) ? $result->response : __('api.empty_response', ['company' => $this->company_name]);

            return $this->abort($message);
        }

        return new ResponseData([
            'status' => $result->status,
            'response' => $result->response
        ]);
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
                    $description = 'Inclusion Of Special Perils';
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

    private function getLltpCode($engine_capacity) : string
    {
        $code = '';

        if($engine_capacity <= 1400) {
            $code = 'A';
        } else if($engine_capacity > 1400 && $engine_capacity <= 1650) {
            $code = 'B';
        } else if($engine_capacity > 1650 && $engine_capacity <= 2200) {
            $code = 'C';
        } else if($engine_capacity > 2200 && $engine_capacity <= 3050) {
            $code = 'D';
        } else if($engine_capacity > 3050 && $engine_capacity <= 4100) {
            $code = 'E';
        } else if($engine_capacity > 4100 && $engine_capacity <= 4250) {
            $code = 'F';
        } else if($engine_capacity > 4250 && $engine_capacity <= 4400) {
            $code = 'G';
        } else if($engine_capacity > 4400) {
            $code = 'H';
        }

        return $code;
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
}