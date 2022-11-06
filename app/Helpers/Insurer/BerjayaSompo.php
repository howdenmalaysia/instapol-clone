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
use Illuminate\Support\Str;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\KeyManagement\JWKFactory;

class BerjayaSompo implements InsurerLibraryInterface
{
    private string $company_id;
    private string $company_name;

    private string $agent_code;
    private string $host;
    private string $client_id;
    private string $client_key;
    private string $token;
    private string $encryption_salt;

    // Settings
    private const ADJUSTMENT_RATE_DOWN = 10;
    private const ADJUSTMENT_RATE_UP = 0;
    private const DATE_FORMAT = 'd-m-Y';
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 500000;
    private const ALLOWED_GAP_IN_COVER = 15;

    private const EXTRA_COVERAGE_LIST = ['89', '97', '112', '25', '97A', '111', '101', 'PLC', '72', 'LOUP', 'PA*P', 'EHRP', 'SPTP', 'BTWP', 'TOWP'];
    private const CART_AMOUNT_LIST = [50, 100, 200];
    private const CART_DAY_LIST = [7, 14, 21];

    public function __construct(int $insurer_id, string $insurer_name)
    {
        $this->company_id = $insurer_id;
        $this->company_name = $insurer_name;

        $this->agent_code = config('insurer.config.bsib.agent_code');
        $this->host = config('insurer.config.bsib.host');
        $this->client_id = config('insurer.config.bsib.client_id');
        $this->client_key = config('insurer.config.bsib.secret_key');
        $this->token = config('insurer.config.bsib.auth_token');
        $this->encryption_salt = Str::uuid()->toString();
    }

    public function vehicleDetails(object $input) : object
    {
        $data = (object) [
            'vehicle_number' => $input->vehicle_number,
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'email' => $input->email,
            'postcode' => $input->postcode,
            'gender' => $input->gender ?? '',
            'phone_number' => $input->phone_number,
            'vehicle' => (object) [
                'nvic' => $input->nvic ?? ''
            ]
        ];

        // default occupation to get vehicle details
        switch($input->id_type) {
            case config('setting.id_type.nric_no'): {
                $data->occupation = 'EXECUTIVE';
                break;
            }
            case config('setting.id_type.company_registration_no'): {
                $data->occupation = 'RETAIL TRADING';

                break;
            }
            default: {
                return $this->abort(__('api.unsupported_id_type'), config('setting.response_codes.unsupported_id_types'));
            }
        }

        $vix = $this->getQuotation($data);

        if (!$vix->status) {
            return $this->abort($vix->response);
        }

        // If failed because no NVIC specified, default to first NVIC returned and try again
        if($vix->response->RESPONSE_STATUS === 'FAILURE' && Str::contains($vix->response->ERROR[0]->ERROR_DESC, 'MULTIPLE NVIC RECEIVED')) {
            $data->vehicle->nvic = explode('|', $vix->response->NVIC_CODE)[0];
            $vix = $this->getQuotation($data);

            if (!$vix->status) {
                return $this->abort($vix->response);
            }
        }

        // Get coverage dates
        $inception_date = Carbon::createFromFormat('d-m-Y', $vix->response->INCEPTIONDATE);
        $expiry_date = Carbon::createFromFormat('d-m-Y', $vix->response->CNEXPIRYDATE)->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');

        // Check Inception Date
        if ($inception_date < $today) {
            $gap_in_cover = abs(Carbon::today()->diffInDays($inception_date));
            if ($gap_in_cover > self::ALLOWED_GAP_IN_COVER) {
                return $this->abort(__('api.gap_in_cover', ['days' => $gap_in_cover]), config('setting.response_codes.gap_in_cover'));
            }
        } else {
            // Check 2 Months Before
            if (Carbon::parse($today)->addMonths(2)->lessThan($inception_date)) {
                return $this->abort(__('api.earlier_renewal'), config('setting.response_codes.earlier_renewal'));
            }
        }

        // Check Sum Insured
        $sum_insured = formatNumber($vix->response->SUM_INSURED, 0);
        if ($sum_insured < self::MIN_SUM_INSURED || roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true) > self::MAX_SUM_INSURED) {
            return $this->abort(__('api.sum_insured_referred_between', [
                'min_sum_insured' => self::MIN_SUM_INSURED,
                'max_sum_insured' => self::MAX_SUM_INSURED
            ]), config('setting.response_codes.sum_insured_referred'));
        }

        $variants = [];
        array_push($variants, new VariantData([
            'nvic' => (string) $vix->response->NVIC_CODE,
            'sum_insured' => floatval($vix->response->SUM_INSURED),
            'variant' => '-',
        ]));

        return (object) [
            'status' => true,
            'response' => new VIXNCDResponse([
                'chassis_number' => $vix->response->CHASSIS_NO,
                'class_code' => $vix->response->VEHICLE_CLASS,
                'coverage' => 'Comprehensive',
                'cover_type' => $vix->response->COVERAGE_TYPE,
                'engine_capacity' => $vix->response->CAPACITY,
                'engine_number' => $vix->response->ENGINE_NUMBER,
                'expiry_date' => $expiry_date,
                'inception_date' => $inception_date->format('Y-m-d'),
                'make' => $vix->response->MAKE_DESC,
                'manufacture_year' => $vix->response->YEAR_OF_MANUFACTURING,
                'max_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true, self::MAX_SUM_INSURED),
                'min_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_DOWN, false, self::MIN_SUM_INSURED),
                'model' => str_replace($vix->response->MAKE_DESC . ' ', '', $vix->response->MODEL_DESC),
                'ncd_percentage' => floatval($vix->response->NCD_PERCENT),
                'seating_capacity' => $vix->response->SEAT,
                'sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true, self::MAX_SUM_INSURED),
                'sum_insured_type' => $vix->response->ENDT_CLAUSE_CODE === 113 ? 'Market Value' : 'Agreed Value',
                'variants' => $variants,
                'vehicle_number' => $input->vehicle_number,
                'vehicle_body_code' => $vix->response->VEHICLE_BODY
            ])];
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
                    case '89': { // Windscreen Damage
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

        $data = (object) [
            'vehicle_number' => $input->vehicle_number,
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'gender' => $input->gender,
            'marital_status' => $input->marital_status,
            'region' => $input->region,
            'vehicle' => $vehicle,
            'extra_cover' => $input->extra_cover,
            'email' => $input->email,
            'phone_number' => $input->phone_number,
            'unit_no' => $input->unit_no ?? null,
            'building_name' => $input->building_name ?? null,
            'address_one' => $input->address_one ?? null,
            'address_two' => $input->address_two ?? null,
            'city' => $input->city ?? '',
            'postcode' => $input->postcode,
            'state' => $input->state ?? '',
            'occupation' => $input->occupation,
        ];

        $motor_premium = $this->getQuotation($data);

        if (!$motor_premium->status) {
            return $this->abort($motor_premium->response);
        }

        // To check if the vehicle is entitled to purchase windscreen add ons
        if($motor_premium->response->WINDSCREEN_EXTCVG_ALLOW === 'N') {
            unset($input->extra_cover[array_search('89', array_column($input->extra_cover, 'extra_cover_code'))]);
        }

        if(!empty($motor_premium->response->ADD_ONS)) {
            array_map(function($extra_cover, $extra_benefit) use($motor_premium, &$pa) {
                if($extra_cover->extra_cover_code == $extra_benefit->CODE && $extra_benefit->CODE != 'PA*P') {
                    $extra_cover->premium = formatNumber($extra_benefit->PREMIUM);
                    $extra_cover->extra_cover_description = str_replace(['cart', 'Ncd', 'Add', 'On', 'E-ride'], ['CART', 'NCD',  '', '', 'E-Ride'], ucwords(Str::lower($extra_benefit->DESCRIPTION)));
                } else if ($extra_benefit->CODE == 'PA*P' && $extra_cover->extra_cover_code == 'PA*P') {
                    $pa = (object) [
                        'name' => str_replace(['Add', 'On'], [''], ucwords(Str::lower($extra_benefit->DESCRIPTION))),
                        'plan' => $this->getPAPlanCode($extra_benefit->SUM_INSURED),
                        'gross_premium' => formatNumber($extra_benefit->PREMIUM),
                        'sst_percent' => formatNumber($motor_premium->response->SST_PERCENTAGE),
                        'sst_amount' => formatNumber(($motor_premium->response->SST_PERCENTAGE / 100) * $extra_benefit->PREMIUM),
                        'stamp_duty' => formatNumber(0),
                        'net_premium' => formatNumber(0),
                        'total_payable' => formatNumber($extra_benefit->PREMIUM + ($motor_premium->response->SST_PERCENTAGE / 100) * $extra_benefit->PREMIUM),
                    ];

                    $extra_cover->premium = formatNumber($extra_benefit->PREMIUM);
                    $extra_cover->extra_cover_description = ucwords(Str::lower($extra_benefit->DESCRIPTION));
                }
            }, $input->extra_cover, $motor_premium->response->ADD_ONS);
        }

        // if full quote, use back the premium without extra cover
        // also return extra cover list and vehicle data
        $response = new PremiumResponse([
            'basic_premium' => formatNumber($motor_premium->response->BASIC_PREMIUM),
            'ncd_percentage' => $motor_premium->response->NCD_PERCENT,
            'ncd_amount' => formatNumber($motor_premium->response->NCD_AMOUNT),
            'total_benefit_amount' => formatNumber($motor_premium->response->EXTRACOVERAGE_AMOUNT),
            'gross_premium' => formatNumber($motor_premium->response->GROSS_PREMIUM),
            'sst_percent' => formatNumber($motor_premium->response->SST_PERCENTAGE),
            'sst_amount' => formatNumber($motor_premium->response->SST_PREMIUM),
            'stamp_duty' => formatNumber($motor_premium->response->STAMP_DUTY),
            'excess_amount' => formatNumber(0),
            'total_payable' => formatNumber($motor_premium->response->AMT_PAY_CLIENT),
            'net_premium' => formatNumber($motor_premium->response->AMT_PAY_CLIENT - $motor_premium->response->COMMISSION),
            'extra_cover' => $input->extra_cover,
            'personal_accident' => $pa,
            'quotation_number' => $motor_premium->response->QUOTATION_NO,
        ]);

        if ($full_quote) {
            // Revert to premium without extra covers
            $response->basic_premium = $basic_premium;
            $response->ncd_percentage = $ncd_percentage;
            $response->ncd_amount = $ncd_amount;
            $response->total_benefit_amount = $total_benefit_amount;
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

        $result = $this->getQuotation($data, 'MCN');

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

    private function getQuotation(object $input, string $type = 'MQT')
    {
        // Format Address
        $address = [$input->unit_no ?? '', $input->building_name ?? '', $input->address_one ?? 'Level 27, Menara Etiqa', $input->address_two ?? ''];

        if (!empty($address[1])) {
            $address[0] = trim($address[0] . ' ' . $address[1]);
            $address[1] = $address[2];
            $address[2] = $address[3];
        } else {
            $address[0] = trim($address[0] . ' ' . $address[2]);
            $address[1] = $address[3];
            $address[3] = $address[2] = '';
        }

        $remaining = '';
        foreach($address as $index => $_address) {
            $_address = str_replace(',', '', $_address);

            if(!empty($remaining)) {
                $_address = trim($remaining) . ' ' . $_address;
                $remaining = '';
            }

            if(strlen($_address) > 50) {
                $new_address = $_address;

                while(strlen($new_address) > 50) {
                    $remaining = Str::afterLast($new_address, ' ') .  ' ' . $remaining;
                    $new_address = Str::beforeLast($new_address, ' ');
                }

                $address[$index] = trim($new_address);
            } else {
                $address[$index] = trim($_address);
            }
        }

        $extra_cover = [];
        if(!empty($input->extra_cover)) {
            foreach($input->extra_cover as $extra_cover) {
                $item = (object) [
                    'ADD_ON_CODE' => $extra_cover->extra_cover_code,
                    'ADD_ON_SUM_INS' => intval($extra_cover->sum_insured ?? 0),
                ];

                if(!empty($extra_cover->cart_amount)) {
                    $item->CART_DAYS = intval($extra_cover->cart_day);
                    $item->CART_AMOUNT = intval($extra_cover->cart_amount);
                } else if($extra_cover->extra_cover_code == 'LOUP') {
                    $item->EXT_PLAN_TYPE = $this->getLossOfUsePlanCode($extra_cover->sum_insured);
                    $item->EXT_PLAN_SUM_INS = intval($extra_cover->sum_insured);
                    $item->ADD_ON_SUM_INS = 0;
                } else if($extra_cover->extra_cover_code == 'PA*P') {
                    $item->EXT_PLAN_TYPE = $this->getPAPlanCode($extra_cover->sum_insured);
                    $item->EXT_PLAN_SUM_INS = intval($extra_cover->sum_insured);
                    $item->ADD_ON_SUM_INS = 0;
                }

                array_push($extra_cover, $item);
            }
        }

        $occupation_code = $type_of_business = $driving_experience = '';

        switch($input->id_type) {
            case config('setting.id_type.nric_no'): {
                $contact_type = 'I'; // INDIVIDUAL
                $salutation = $input->gender == 'F' ? 'MS' : 'MR';
                $nric_number = $input->id_number;
                $marital_status = $input->marital_status ?? 'S';
                $vehicle_usage = '11'; // INDIVIDUAL
                $ownership_type = '2'; // INDIVIDUAL
                $occupation_code = $this->getOccupationCode($input->occupation);
                $driving_experience = (getAgeFromIC($input->id_number) - 18) < 0 ? 0 : (getAgeFromIC($input->id_number) - 18);

                break;
            }
            case config('setting.id_type.company_registration_no'): {
                // Optional Fields for Company Registered Vehicles
                $marital_status = '';

                $contact_type = 'B'; // BUSINESS
                $salutation = 'OTHERS'; // OTHERS
                $company_registration_number = $input->id_number;
                $type_of_business = 'A002'; // BUSINESS & PROFESSIONAL (OFFICE)
                $vehicle_usage = '12'; // BUSINESS
                $ownership_type = '1'; // COMPANY
                $type_of_business = $this->getOccupationCode($input->occupation, 'business');
                $driving_experience = 0;

                break;
            }
            default: {
                return $this->abort(__('api.unsupported_id_type'), config('setting.response_codes.unsupported_id_types'));
            }
        }

        $data = [
            'TYPE' => $type,
            'KEY' => 'HIB' . Carbon::now()->timestamp,
            'CLIENT_PROFILE' => [
                'ACCODE' => $this->agent_code,
                'BUSINESS_REGISTRATION_NO' => $company_registration_number ?? '',
                'CLIENT_NAME' => $input->name ?? config('app.name'),
                'EMAIL' => $input->email,
                'INS_TYPE_OF_BUSINESS' => $type_of_business,
                'INSURED_CONTACT_TYPE' => $contact_type,
                'MARITAL_STATUS' => $marital_status,
                'MOBILE_NO' => $input->phone_number,
                'NATIONALITY' => 'MYS',
                'NEW_IC' => $nric_number ?? '',
                'OCCP_CODE' => $occupation_code,
                'PERMANENT_ADDRESS_1' => $address[0],
                'PERMANENT_ADDRESS_2' => $address[1] ?? '',
                'PERMANENT_ADDRESS_3' => $address[2] ?? '',
                'PERMANENT_POSTCODE' => $input->postcode,
                'SALUTATION' => $salutation,
                'SAME_AS_PERMA_ADDR_IND' => 'Y',
            ],
            'COVER_NOTE' => [
                'ADDITIONAL_USAGE' => '1', // INCLUDING GOODS [Private Use (Drive to Work)]
                'ANTITHEFT' => '03', // W/O MECH DEV: FACTORY ALARM
                'COVER_NOTE_TYPE' => 'NWOO', // NEW BUSINESS - OLD VEHICLE, OLD REGISTRATION
                'CN_EXPIRY_DATE' => empty($input->vehicle->expiry_date) ? Carbon::today()->addYear()->subDay()->format(self::DATE_FORMAT) : Carbon::parse($input->vehicle->expiry_date)->format(self::DATE_FORMAT),
                'CN_INCEPTION_DATE' => empty($input->vehicle->inception_date) ? Carbon::today()->format(self::DATE_FORMAT) : Carbon::parse($input->vehicle->inception_date)->format(self::DATE_FORMAT),
                'COVERAGE_TYPE' => '05', // SOMPO MOTOR
                'DRIVER_EXPERIENCE' => $this->getDriverExperienceCode($driving_experience),
                'DRIVER_RELATIONSHIP' => '0' . ($input->relationship ?? 1), // PARENT / PARENT-IN-LAW
                'FINANCIAL_TYPE' => 'NA', // N/A
                'GARAGE' => '03', // LOCKED COMPOUND
                'NVIC_CODE' => $input->vehicle->nvic,
                'OWNERSHIP_TYPE' => $ownership_type,
                'SAFE_DEVICE' => '06', // ABS & 2 AIRBAGS
                'SEAT' => $input->vehicle->extra_attribute->seating_capacity ?? '5',
                'VEHICLE_CLASS' => $input->vehicle->extra_attribute->vehicle_class ?? '01', // PRIVATE VEHICLE, EXCLUDING GOODS
                'VEHICLE_NUMBER' => $input->vehicle_number,
                'VEHICLE_USAGE' => $vehicle_usage,
            ],
            'ADD_ONS' => $extra_cover,
            'QUESTIONAIRES' => [
                'SUB_QUESTION' => [
                    'EMAIL_TO_CLIENT_IND' => 'Y', // Email Policy Schedule to Customers
                ]
            ]
        ];

        if(!empty($input->vehicle->sum_insured)) {
            $data['COVER_NOTE']['VEHICLE_SUM_INSURED'] = $input->vehicle->sum_insured;
        }

        $result = $this->cURL($data);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return (object) ['status' => true, 'response' => $result->response];
    }

    private function encrypt($data)
    {
        $encrypter = new JWEBuilder(new AlgorithmManager([new A256KW()]), new AlgorithmManager([new A256CBCHS512()]), new CompressionMethodManager());
        $serializer = new CompactSerializer();

        $jwk = JWKFactory::createFromSecret(openssl_pbkdf2($this->client_key, $this->encryption_salt, 32, 65536, 'sha256'), ['alg' => 'A256KW', 'enc' => 'A256CBC-HS512']);
        $jwe = $encrypter->create()->withPayload(json_encode($data))->withSharedProtectedHeader(['alg' => 'A256KW', 'enc' => 'A256CBC-HS512'])->addRecipient($jwk)->build();
        $encrypted = $serializer->serialize($jwe, 0);
        
        return $encrypted;
    }

    private function decrypt(string $data, string $salt)
    {
        $decrypter = new JWEDecrypter(new AlgorithmManager([new A256KW()]), new AlgorithmManager([new A256CBCHS512()]), new CompressionMethodManager());
        $serializer = new CompactSerializer();

        $jwk = JWKFactory::createFromSecret(openssl_pbkdf2($this->client_key, $salt, 32, 65536, 'sha256'), ['alg' => 'A256KW', 'enc' => 'A256CBC-HS512']);
        $jwe = $serializer->unserialize($data);
        $success = $decrypter->decryptUsingKey($jwe, $jwk, 0);

        if(!$success) {
            return $this->abort('Unable to decrypt the response.');
        }

        return json_decode($jwe->getPayload());
    }

    private function cURL(array $form, string $path = '/nsure/1.0.0/cnap', string $method = 'POST', int $timeout = 60) : ResponseData
    {
        $payload = (object) [
            'encryptedPayload' => $this->encrypt($form),
        ];

        // initialize api request
        $request_options = [
            'timeout' => $timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Encryption-Salt' => $this->encryption_salt,
                'Client-Id' => $this->client_id,
                'Authorization' => 'Bearer ' . $this->token
            ],
            'json' => $payload
        ];

        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $this->host,
            'path' => $path,
            'request_header' => json_encode($request_options['headers']),
            'request' => json_encode($form),
        ]);

        // API call
        $result = HttpClient::curl($method, $this->host . $path, $request_options);

        if(!empty(json_decode($result->response)->encryptedPayload)) {
            $decrypted_response = json_decode($this->decrypt(json_decode($result->response)->encryptedPayload, $result->response_header['Encryption-Salt'][0])->text);

            if(isset($decrypted_response->status) && !$decrypted_response->status) {
                // Update the API log
                $log = APILogs::find($log->id)
                    ->update([
                        'response_header' => json_encode($result->response_header),
                        'response' => json_encode($decrypted_response)
                    ]);

                return $this->abort($decrypted_response->response);
            }

            $result->response = $decrypted_response;
        }

        // Update the API log
        $log = APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => json_encode($decrypted_response)
            ]);

        if($result->status) {
            if($decrypted_response->RESPONSE_STATUS === 'FAILURE' && !Str::contains($decrypted_response->ERROR[0]->ERROR_DESC, 'MULTIPLE NVIC RECEIVED')) {
                $messages = $codes = [];

                foreach($decrypted_response->ERROR as $error) {
                    array_push($messages, $error->ERROR_DESC);
                    array_push($codes, $error->ERROR_CODE);
                }

                return $this->abort(__('api.api_error', ['company' => $this->company_name, 'code' => implode(', ', $codes) . '-' . implode(', ', $messages)]));
            }
        } else {
            return $this->abort($decrypted_response);
        }

        return new ResponseData([
            'status' => $result->status,
            'response' => $decrypted_response
        ]);
    }

    private function getDriverExperienceCode($driving_experience)
    {
        $code = '';

        if($driving_experience <= 1) {
            $code = 1;
        } else if ($driving_experience > 1 && $driving_experience <= 2) {
            $code = 2;
        } else if ($driving_experience > 2 && $driving_experience <= 3) {
            $code = 3;
        } else if ($driving_experience > 3 && $driving_experience <= 5) {
            $code = 4;
        } else if ($driving_experience > 5 && $driving_experience <= 10) {
            $code = 5;
        } else if ($driving_experience > 10 && $driving_experience <= 15) {
            $code = 6;
        } else if ($driving_experience > 15 && $driving_experience <= 20) {
            $code = 7;
        } else if ($driving_experience > 20 && $driving_experience <= 25) {
            $code = 8;
        } else if ($driving_experience > 25 && $driving_experience <= 30) {
            $code = 9;
        } else if ($driving_experience > 30 && $driving_experience <= 40) {
            $code = 10;
        } else {
            $code = 11;
        }

        return $code;
    }

    private function getLossOfUsePlanCode($sum_insured)
    {
        $code = '';

        switch($sum_insured) {
            case '500': {
                $code = 'LOUP10';
                break;
            }
            case '1000': {
                $code = 'LOUP20';
                break;
            }
            case '2000': {
                $code = 'LOUP30';
                break;
            }
        }

        return $code;
    }

    private function getPAPlanCode($sum_insured)
    {
        $code = '';

        switch($sum_insured) {
            case '25000': {
                $code = 'PA1P';
                break;
            }
            case '50000': {
                $code = 'PA2P';
                break;
            }
            case '100000': {
                $code = 'PA3P';
                break;
            }
        }

        return $code;
    }

    private function getOccupationCode($occupation, $type = 'individual')
    {
        $path = storage_path('Motor/Sompo/occupation.json');

        // Check if Mapping File Exists
        if (!file_exists($path)) {
            return $this->abort(__('api.file_not_found', ['type' => 'Occupation']));
        }

        // Read File Content and Turn Into Collection
        $json = json_decode(file_get_contents($path), true);

        // Search with NVIC
        $result = collect($json[$type])->firstWhere('occupation', $occupation);

        if (empty($result)) {
            // Default to Executive
            return collect($json[$type])->firstWhere('occupation', 'EXECUTIVE');
        }

        return $result['code'];
    }
}