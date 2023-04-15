<?php

namespace App\Helpers\Insurer;

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
use App\Models\Motor\VehicleBodyType;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PacificOrient implements InsurerLibraryInterface
{
    private string $company_id;
    private string $company_name;
    private string $agent_code;
    private string $user_id;
    private string $host;

    // Settings
    private const PRODUCT = 'PC'; // Private Comprehensive
    // LLtP, SRCC, Windscreen, Tinted Flim, NCD Relief
    private const EXTRA_COVERAGE_LIST = ['06', '04', '05', '25', '89', '72', '111'];
    private const ANTI_THEFT_DEVICE = '12'; // M / Device - Alarm w Immobilizer
    private const COUNTRY = 'MYS';
    private const CUSTOMER_CATEGORY = 'I'; // Individual
    private const GARAGE_CODE = '03'; // Within Compound of Residence
    private const SAFETY_FEATURES = '02'; // Driver & Passenger Airbag (2)
    private const COVER_TYPE = '01'; // Comprehensive
    private const OCCUPATION = '99'; // Others
    private const IMPORT_TYPE = '2'; // CKD
    private const PERMITTED_DRIVERS = '01'; // Private Car – Insured & 2 Others
    private const ALLOWED_GAP_IN_COVER = 7; // Days
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 850000;
    private const ADJUSTMENT_RATE_UP = 10;
    private const ADJUSTMENT_RATE_DOWN = 10;

    private const SOAP_ACTION_DOMAIN = 'http://tempuri.org';

    public function __construct(string $company_id, string $company_name)
    {
        $this->company_id = $company_id;
        $this->company_name = $company_name;

        $this->agent_code = config('insurer.config.pno.agent_code');
        $this->user_id = config('insurer.config.pno.user_id');
        $this->host = config('insurer.config.pno.host');
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

        $inception_date = Carbon::createFromFormat('Y-m-d', $vix->response->inception_date);
        $today = Carbon::today();

        // 1. Check Gap In Cover
        if($inception_date->lessThan($today)) {
            $gap_in_cover = $today->diffInDays($inception_date);

            if($gap_in_cover > self::ALLOWED_GAP_IN_COVER) {
                return $this->abort(__('api.gap_in_cover', ['days' => abs($gap_in_cover)]), config('setting.response_codes.gap_in_cover'));
            }
        } else if($today->addMonths(2)->lessThan($inception_date)) {
            return $this->abort(__('api.earlier_renewal'), config('setting.response_codes.earlier_renewal'));
        }

        // 2. Check Sum Insured
        $sum_insured = formatNumber($vix->response->sum_insured, 0);
        if($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(__('api.sum_insured_referred_between', [
                'min_sum_insured' => self::MIN_SUM_INSURED,
                'max_sum_insured' => self::MAX_SUM_INSURED
            ]), config('setting.response_codes.sum_insured_referred'));
        }

        $nvic = explode('|', (string) $vix->response->nvic);

        $variants = [];
        foreach($nvic as $_nvic) {
            // Get Vehicle Details From Mapping Files
            $details = $this->getModelDetails($_nvic);

            array_push($variants, new VariantData([
                'nvic' => $_nvic,
                'sum_insured' => floatval($vix->response->sum_insured),
                'variant' => $details->variant ?? ''
            ]));
        }

        return (object) [
            'status' => true,
            'response' => new VIXNCDResponse([
                'body_type_code' => $details->body_type_code ?? null,
                'body_type_description' => $details->body_type_description ?? null,
                'chassis_number' => $vix->response->chassis_number,
                'coverage' => 'Comprehensive',
                'engine_capacity' => $vix->response->engine_capacity,
                'engine_number' => $vix->response->engine_number,
                'expiry_date' => Carbon::parse($vix->response->expiry_date)->format('d M Y'),
                'inception_date' => Carbon::parse($vix->response->inception_date)->format('d M Y'),
                'make' => $details->make ?? '',
                'make_code' => $vix->response->make,
                'model' => $details->model ?? '',
                'model_code' => $vix->response->model,
                'manufacture_year' => $vix->response->manufacturing_year,
                'max_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_UP, true, self::MAX_SUM_INSURED),
                'min_sum_insured' => roundSumInsured($sum_insured, self::ADJUSTMENT_RATE_DOWN, false, self::MIN_SUM_INSURED),
                'sum_insured' => formatNumber($sum_insured),
                'sum_insured_type' => 'Agreed Value',
                'ncd_percentage' => floatval($vix->response->ncd),
                'seating_capacity' => $vix->response->seating_capacity,
                'variants' => $variants,
                'vehicle_number' => $input->vehicle_number
            ])
        ];
    }

    public function premiumDetails(object $input, $full_quote = false) : object
    {
        $vehicle = $input->vehicle ?? null;
        $ncd_amount = $basic_premium = $total_benefit_amount = $gross_premium = $sst_percent = $sst_amount = $stamp_duty = $excess_amount = $total_payable = 0;
        $id_number = $company_registration_number = '';
        $all_drivers_allowed = false;

        switch($input->id_type) {
            case config('setting.id_type.nric_no'): {
                $id_number = $input->id_number;

                break;
            }
            case config('setting.id_type.company_registration_no'): {
                $company_registration_number = $input->id_number;
                $all_drivers_allowed = true;

                break;
            }
            default: {
                return $this->abort(__('api.unsupported_id_type'), config('setting.response_codes.unsupported_id_types'));
            }
        }

        if($full_quote) {
            $vehicle_vix = $this->vehicleDetails($input);

            if(!$vehicle_vix->status) {
                return $this->abort($vehicle_vix->response, $vehicle_vix->code);
            }

            // Retrieve the Selected Variant via NVIC
            $selected_variant = null;
            if($input->nvic === '-') {
                if(count($vehicle_vix->response->variants) === 1) {
                    $selected_variant = $vehicle_vix->response->variants[0];
                }
            } else {
                foreach($vehicle_vix->response->variants as $variant) {
                    if($variant->nvic === $input->nvic) {
                        $selected_variant = $variant;
                        break;
                    }
                }
            }

            if(empty($selected_variant)) {
                return $this->abort(__('api.variant_not_match'));
            }

            // Format Vehicle Object
            $vehicle = new Vehicle([
                'coverage' => $vehicle_vix->response->coverage,
                'engine_capacity' => $vehicle_vix->response->engine_capacity,
                'expiry_date' => Carbon::createFromFormat('d M Y', $vehicle_vix->response->expiry_date)->format('Y-m-d'),
                'extra_attribute' => (object) [
                    'chassis_number' => $vehicle_vix->response->chassis_number,
                    'cover_type' => $vehicle_vix->response->cover_type,
                    'engine_number' => $vehicle_vix->response->engine_number,
                    'seating_capacity' => $vehicle_vix->response->seating_capacity,
                ],
                'inception_date' => Carbon::createFromFormat('d M Y', $vehicle_vix->response->inception_date)->format('Y-m-d'),
                'make' =>  $vehicle_vix->response->make,
                'manufacture_year' => $vehicle_vix->response->manufacture_year,
                'max_sum_insured' => $vehicle_vix->response->max_sum_insured,
                'min_sum_insured' => $vehicle_vix->response->min_sum_insured,
                'model' => $vehicle_vix->response->model,
                'ncd_percentage' => $vehicle_vix->response->ncd_percentage,
                'nvic' => $selected_variant->nvic,
                'sum_insured_type' => $vehicle_vix->response->sum_insured_type,
                'sum_insured' => $vehicle_vix->response->sum_insured,
                'variant' => $selected_variant->variant,
            ]);

            $data = (object) [
                'age' => $input->age,
                'gender' => $this->getGender($input->gender),
                'id_type' => $input->id_type,
                'id_number' => $id_number,
                'company_registration_number' => $company_registration_number,
                'marital_status' => $this->getMaritalStatusCode($input->marital_status),
                'postcode' => $input->postcode,
                'region' => $input->region,
                'state' => $input->state,
                'vehicle' => $vehicle,
                'vehicle_number' => $input->vehicle_number
            ];

            $premium = $this->getPremium($data);

            if (!$premium->status) {
                return $this->abort($premium->response);
            }

            $excess_amount = formatNumber($premium->response->excess_amount);
            $ncd_amount = formatNumber($premium->response->ncd_amount);
            $basic_premium = formatNumber($premium->response->basic_premium);
            $total_benefit_amount = 0;
            $gross_premium = formatNumber($premium->response->gross_premium);
            $stamp_duty = formatNumber($premium->response->stamp_duty);
            $sst_amount = formatNumber($premium->response->sst_amount);
            $total_payable = formatNumber($premium->response->total_premium);
            $sst_percent = ($sst_amount / $gross_premium) * 100;

            $extra_cover_list = [];
            $available_extra_covers = self::EXTRA_COVERAGE_LIST;
            if(!$all_drivers_allowed) {
                $available_extra_covers = array_diff($available_extra_covers, ['06']);
            }

            foreach($available_extra_covers as $_extra_cover_code) {
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
                    case '89': {
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
                    case '06': {
                        $extra_cover->selected = true;
                        $extra_cover->readonly = true;

                        break;
                    }
                    case '72':
                    case '111': {
                        $sum_insured_amount = $vehicle_vix->response->sum_insured;
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

        $formatted_extra_cover = array_filter($input->extra_cover, function ($extra_cover) {
            return $extra_cover->extra_cover_code != '06';
        });

        $data = (object) [
            'age' => $input->age,
            'additional_driver' => $input->additional_driver,
            'company_registration_number' => $company_registration_number,
            'email' => $input->email,
            'extra_cover' => $formatted_extra_cover,
            'gender' => $this->getGender($input->gender),
            'id_type' => $input->id_type,
            'id_number' => $id_number,
            'marital_status' => $this->getMaritalStatusCode($input->marital_status),
            'postcode' => $input->postcode,
            'region' => $input->region,
            'state' => $input->state,
            'sum_insured' => $vehicle->sum_insured,
            'vehicle' => $vehicle,
            'vehicle_number' => $input->vehicle_number,
        ];

        $premium = $this->getPremium($data);

        if(!$premium->status) {
            return $this->abort($premium->response);
        }

        if(!empty($premium->response->extra_coverage)) {
            foreach($premium->response->extra_coverage as $extra) {
                $total_benefit_amount += (float) $extra->premium;

                foreach($input->extra_cover as $extra_cover) {
                    if((string) $extra->coverageId === $extra_cover->extra_cover_code) {
                        $extra_cover->premium = formatNumber((float) $extra->premium);
                        if($extra_cover->extra_cover_code != '06') {
                            $extra_cover->selected = (float) $extra->premium == 0;
                        }

                        if(!empty($extra->sumInsured)) {
                            $extra_cover->sum_insured = formatNumber((float) $extra->sumInsured);
                        }
                    }
                }
            }
        }

        $response = new PremiumResponse([
            'act_premium' => formatNumber($premium->response->act_premium),
            'basic_premium' => formatNumber($premium->response->basic_premium),
            'detariff' => $premium->response->detariff,
            'detariff_premium' => formatNumber($premium->response->detariff_premium),
            'discount' => formatNumber($premium->response->discount),
            'discount_amount' => formatNumber($premium->response->discount_amount),
            'excess_amount' => formatNumber($premium->response->excess_amount),
            'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
            'gross_premium' => formatNumber($premium->response->gross_premium),
            'loading' => formatNumber($premium->response->loading_amount),
            'ncd_amount' => formatNumber($premium->response->ncd_amount),
            'net_premium' => formatNumber($premium->response->basic_nett_premium + $premium->response->sst_amount + $premium->response->stamp_duty),
            'sum_insured' => formatNumber($premium->response->sum_insured),
            'min_sum_insured' => formatNumber($vehicle_vix->response->min_sum_insured ?? $vehicle->min_sum_insured),
            'max_sum_insured' => formatNumber($vehicle_vix->response->max_sum_insured ?? $vehicle->max_sum_insured),
            'sum_insured_type' => $vehicle->sum_insured_type,
            'sst_amount' => formatNumber($premium->response->sst_amount),
            'sst_percent' => formatNumber(ceil(($premium->response->sst_amount / $premium->response->gross_premium) * 100)),
            'stamp_duty' => formatNumber($premium->response->stamp_duty),
            'tariff_premium' => formatNumber($premium->response->tariff_premium),
            'total_benefit_amount' => formatNumber($total_benefit_amount),
            'total_payable' => formatNumber(roundPricing($premium->response->total_premium)),
            'request_id' => $premium->response->request_id,
            'named_drivers_needed' => $input->id_type === config('setting.id_type.nric_no'),
        ]);

        if($full_quote) {
            // Revert to premium without extra covers
            $response->excess_amount = $excess_amount;
            $response->basic_premium = $basic_premium;
            $response->ncd_amount = $ncd_amount;
            $response->gross_premium = $gross_premium;
            $response->stamp_duty = $stamp_duty;
            $response->sst_amount = $sst_amount;
            $response->sst_percent = $sst_percent;
            $response->total_benefit_amount = array_filter($input->extra_cover, function ($extra_cover) {
                return $extra_cover->extra_cover_code == '06';
            })[0]->premium ?? 0;
            $response->total_payable = roundPricing($total_payable);

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
            'age' => $input->age,
            'additional_driver' => $input->additional_driver,
            'email' => $input->email,
            'extra_cover' => $input->extra_cover,
            'gender' => $input->gender,
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'marital_status' => $input->marital_status,
            'postcode' => $input->postcode,
            'region' => $input->region,
            'state' => $input->state,
            'vehicle' => $input->vehicle,
            'vehicle_number' => $input->vehicle_number,
        ];

        $quotation = $this->premiumDetails($data);

        if(!$quotation->status) {
            return $this->abort($quotation->response);
        }

        return (object) [
            'status' => true,
            'response' => new PremiumResponse([
                'act_premium' => $quotation->response->act_premium,
                'basic_premium' => $quotation->response->basic_premium,
                'excess_amount' => $quotation->response->excess_amount,
                'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
                'gross_premium' => $quotation->response->gross_premium,
                'ncd_amount' => $quotation->response->ncd_amount,
                'net_premium' => $quotation->response->net_premium,
                'sst_amount' => $quotation->response->sst_amount,
                'sst_percent' => $quotation->response->sst_percent,
                'stamp_duty' => $quotation->response->stamp_duty,
                'total_benefit_amount' => $quotation->response->total_benefit_amount,
                'total_payable' => $quotation->response->total_payable,
                'request_id' => $quotation->response->request_id,
                'loading' => $quotation->response->loading,
                'sum_insured' => $quotation->response->sum_insured,
                'sum_insured_type' => $input->vehicle->sum_insured_type,
                'min_sum_insured' => floatval($input->vehicle->min_sum_insured),
                'max_sum_insured' => floatval($input->vehicle->max_sum_insured),
                'named_drivers_needed' => true,
            ])
        ];
    }

    public function submission(object $input) : object
    {
        // Get Extra Attribute
        $extra_attribute = json_decode($input->insurance->extra_attribute->value);

        switch($input->id_type) {
            case config('setting.id_type.nric_no'): {
                $input->gender = $input->insurance->holder->gender;
                $input->age = $input->insurance->holder->age;
                $input->marital_status = $this->getMaritalStatusCode($input->insurance_motor->marital_status);

                break;
            }
            case config('setting.id_type.company_registration_no'): {
                $input->company_registration_number = $input->id_number;
                $input->gender = $this->getGender('O');
                $input->age = 0;
                $input->marital_status = $this->getMaritalStatusCode('O');

                break;
            }
            default: {
                return $this->abort(__('api.unsupported_id_type'), config('setting.response_codes.unsupported_id_types'));
            }
        }

        $input->postcode = $input->insurance->address->postcode;

        $input->vehicle = (object) [
            'expiry_date' => $input->insurance->expiry_date,
            'inception_date' => $input->insurance->inception_date,
            'manufacture_year' => $input->insurance_motor->manufactured_year,
            'ncd_percentage' => $input->insurance_motor->ncd_percentage,
            'nvic' => $input->insurance_motor->nvic,
            'sum_insured' => formatNumber($input->insurance_motor->market_value),
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
                'extra_cover_code' => $extra_cover->code,
                'extra_cover_description' => $extra_cover->description,
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

        $sequence = [];
        foreach($premium_result->response->extra_coverage as $extra) {
            array_push($sequence, $extra->coverageId);
        }

        $input->sequence = $sequence;

        $input->premium_details = $premium_result->response;
        $input->vehicle->extra_attribute->request_id = $premium_result->response->request_id;

        $result = $this->issueCoverNote($input);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => (object) [
                'policy_number' => $result->response->policyNo
            ]
        ]);
    }

    public function getToken() : string
    {
        $path = 'POAT/Service.svc';

        $data = [
            'agent_code' => $this->agent_code,
            'user_id' => $this->user_id,
            'product' => self::PRODUCT,
            'ref_no' => config('setting.howden.short_code') . Str::random(13),
            'path' => $this->host . $path,
            'soap_action' => self::SOAP_ACTION_DOMAIN . '/' . $path,
        ];

        $xml = view('backend.xml.pacific.get_token')->with($data)->render();

        $result = $this->cURL($path, $xml, self::SOAP_ACTION_DOMAIN . '/IAccessToken/GetAccessToken');

        $data = $result->response->GetAccessTokenResponse->GetAccessTokenResult;
        if(!$result->status) {
            return $this->abort($data->respDescription);
        }

        return $data->accessToken;
    }

    private function getVIXNCD(array $input) : ResponseData
    {
        $path = 'getvehicleinfo/GetVehicleInfo.asmx';
        $input['token'] = $this->getToken();

        $xml = view('backend.xml.pacific.vehicle_details')->with($input)->render();

        $result = $this->cURL($path, $xml, self::SOAP_ACTION_DOMAIN . '/RequestVehicleInfo');
        $data = $result->response->RequestVehicleInfoResponse->RequestVehicleInfoResult;

        if(!$result->status) {
            return $this->abort($result->response);
        }

        // 1. Check for Error
        if(!empty($data->ErrorDesc)) {
            return $this->abort("P&O Error! {$data->ErrorDesc}");
        }

        $response = (object) [
            'chassis_number' => (string) $data->ChassisNumber,
            'engine_capacity' => (float) $data->EngineCC,
            'engine_number' => (string)$data->EngineNumber,
            'error_description' => (string) $data->ErrorDesc,
            'expiry_date' => (string) $data->NextXDate,
            'inception_date' => (string) $data->NextEDate,
            'make' => (int) $data->VehicleMake,
            'manufacturing_year' => (int) $data->YearManufactured,
            'model' => (int) $data->VehicleModel,
            'ncd' => (float) $data->NCD,
            'nvic' => (string) $data->NVIC,
            'seating_capacity' => (int) $data->SeatingCapacity,
            'sum_insured' => (float) str_replace(',', '', $data->SumInsured)
        ];

        return new ResponseData([
            'response' => $response,
        ]);
    }

    public function getPremium(object $input) : ResponseData
    {
        $path = 'poiapiv2/Insurance.svc';
        $token = $this->getToken();

        $data = [
            'all_rider' => 'N', // Default to No,
            'is_company' => $input->id_type === config('setting.id_type.company_registration_no') ? 'Y' : 'N',
            'company_registration_number' => $input->company_registration_number ?? '',
            'coverage' => self::COVER_TYPE,
            'effective_date' => $input->vehicle->inception_date,
            'expiry_date' => $input->vehicle->expiry_date,
            'extra_cover' => $input->extra_cover ?? [],
            'gender' => $input->gender,
            'age' => $input->age,
            'marital_status' => $input->marital_status,
            'ncd_percentage' => $input->vehicle->ncd_percentage,
            'additional_driver_count' => $input->id_type === config('setting.id_type.company_registration_no') ? 1 : count($input->additional_driver ?? []) + 1,
            'id_number' => $input->id_number,
            'hire_purchase' => 'N',
            'postcode' => $input->postcode,
            'reference_number' => Str::uuid(),
            'sum_insured' => $input->sum_insured ?? $input->vehicle->sum_insured,
            'token' => $token,
            'nvic' => $input->vehicle->nvic,
            'vehicle_number' => $input->vehicle_number,
            'vehicle_type' => $input->vehicle->extra_attribute->seating_capacity <= 5 ? '2-01' : '2-06'
        ];

        $xml = view('backend.xml.pacific.premium')->with($data)-> render();

        $result = $this->cURL($path, $xml, self::SOAP_ACTION_DOMAIN . '/IInsurance/PremiumRequest');
        $data = $result->response->PremiumRequestResponse->PremiumRequestResult;

        if(!$result->status) {
            return $this->abort($result->response);
        }

        // 1. Check for Error
        if((string) $data->respCode !== '000') {
            return $this->abort("P&O Error! {$data->respDescription}");
        }

        // 2. Retrieve Extra Cover
        $extra_cover = [];
        foreach($data->extraCoverage->extraCoverageResp as $extra) {
            array_push($extra_cover, $extra);
        }

        $response = (object) [
            'act_premium' => (float) $data->actPremium,
            'basic_premium' => (float) $data->basicPremium,
            'basic_nett_premium' => (float) $data->basicNettPremium,
            'detariff' => (string) $data->detariff,
            'detariff_premium' => (float) $data->detariffPremium,
            'discount' => (float) $data->discount,
            'discount_amount' => (float) $data->discountAmt,
            'excess_amount' => (float) $data->excessAmt,
            'extra_coverage' => $extra_cover,
            'gross_premium' => (float) $data->grossPremium,
            'loading_amount' => (float) $data->loadingAmt,
            'ncd_amount' => (float) $data->ncdAmt,
            'non_act_premium' => (float) $data->nonActPrem,
            'reference_number' => (string) $data->refNo,
            'request_id' => (string) $data->requestId,
            'sst_amount' => (float) $data->serviceTaxAmt,
            'stamp_duty' => (float) $data->stampDuty,
            'sum_insured' => (float) $data->sumInsured,
            'tariff_premium' => (float) $data->tariffPremium,
            'total_premium' => (float) $data->totalPremium
        ];

        return new ResponseData([
            'response' => $response,
        ]);
    }

    public function issueCoverNote(object $input) : ResponseData
    {
        $path = 'poiapiv2/Insurance.svc';
        $token = $this->getToken();

        $formatted_extra_cover = [];
        foreach($input->extra_cover as $extra) {
            if(in_array($extra->extra_cover_code, ['04', '72', '111'])) {
                array_push($formatted_extra_cover, (object) [
                    'extra_cover_code' => $extra->extra_cover_code,
                    'premium' => $extra->premium
                ]);
            } else {
                array_push($formatted_extra_cover, (object) [
                    'extra_cover_code' => $extra->extra_cover_code,
                    'sum_insured' => $extra->sum_insured,
                    'premium' => $extra->premium
                ]);
            }
        }

        usort($formatted_extra_cover, function($a, $b) use($input) {
            $pos_a = array_search($a->extra_cover_code, $input->sequence);
            $pos_b = array_search($b->extra_cover_code, $input->sequence);

            return $pos_a - $pos_b;
        });

        $data = [
            'additional_driver' => $input->additional_driver,
            'address_one' => $input->insurance->address->address_one,
            'address_two' => $input->insurance->address->address_two,
            'age' => $input->age,
            'anti_theft' => self::ANTI_THEFT_DEVICE,
            'chassis_number' => $input->vehicle->extra_attribute->chassis_number,
            'city' => $input->insurance->address->city,
            'commission_amount' => formatNumber($input->premium_details->gross_premium * 0.1),
            'commission_rate' => 10,
            'country_code' => self::COUNTRY,
            'cover_region_code' => substr(strtoupper($input->region), 0, 1),
            'cover_note_date' => $input->insurance->quotation_date,
            'cover_note_effective_date' => $input->vehicle->inception_date,
            'cover_note_expiry_date' => Carbon::parse($input->vehicle->inception_date)->addYear()->subDay()->format('Y-m-d'),
            'customer_category' => self::CUSTOMER_CATEGORY,
            'customer_name' => $input->insurance->holder->name,
            'date_of_birth' => Carbon::parse($input->insurance->holder->date_of_birth)->format('Y-m-d'),
            'email' => $input->insurance->holder->email_address,
            'email_aggregator' => 'instapol@my.howdengroup.com',
            'engine_number' => $input->vehicle->extra_attribute->engine_number,
            'extra_coverage' => $formatted_extra_cover,
            'garage_code' => self::GARAGE_CODE,
            'gender' => $this->getGender($input->gender, true),
            'id_number' => $input->insurance->holder->id_number,
            'import_type' => self::IMPORT_TYPE,
            'insured_age' => getAgeFromIC($input->insurance->holder->id_number),
            'insured_gender' => $this->getGender($input->insurance->holder->gender, true),
            'logbook_number' => 'A001',
            'marital_status' => $input->marital_status,
            'ncd_amount' => $input->insurance_motor->ncd_amount,
            'ncd_percentage' => $input->vehicle->ncd_percentage,
            'nvic' => $input->vehicle->nvic,
            'occupation' => self::OCCUPATION,
            'permitted_drivers' => self::PERMITTED_DRIVERS,
            'phone_number' => $input->insurance->holder->phone_code . $input->insurance->holder->phone_number,
            'postcode' => $input->insurance->address->postcode,
            'race' => $input->insurance->holder->id_type_id === config('setting.id_type.nric_no') ? strtoupper(raceChecker($input->insurance->holder->name)) : 'CORPORATE',
            'reference_number' => Str::uuid(),
            'safety_feature_code' => self::SAFETY_FEATURES,
            'seat_capacity' => $input->vehicle->extra_attribute->seating_capacity,
            'state_code' => $this->getStateCode($input->insurance->address->state),
            'sum_insured' => $input->vehicle->sum_insured,
            'token' => $token,
            'type_of_cover' => self::COVER_TYPE,
            'vehicle_number' => $input->vehicle_number,
            'year_make' => $input->vehicle->manufacture_year,
            'request_id' => $input->vehicle->extra_attribute->request_id,
            'premium_details' => $input->premium_details
        ];

        $xml = view('backend.xml.pacific.cover_note_submission')->with($data)->render();

        $result = $this->cURL($path, $xml, self::SOAP_ACTION_DOMAIN . '/IInsurance/PolicySubmission');

        if(!$result->status) {
            return $this->abort($result->response);
        }

        $response = (object) [
            'policy_number' => (string) $result->response->PolicySubmissionResult->policyNo
        ];

        return new ResponseData([
            'response' => $result->response
        ]);
    }

    public function cURL(string $path, string $xml, string $soap_action = null, string $method = 'POST', array $header = []) : ResponseData
    {
        // Concatenate URL
        $url = $this->host . $path;

        // Check XML Error
        libxml_use_internal_errors(true);

        // Construct API Request
        $request_options = [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'Accept' => 'text/xml; charset=utf-8',
                'SOAPAction' => $soap_action ?? self::SOAP_ACTION_DOMAIN . '/' . $path
            ],
            'body' => $xml
        ];

        // Log the Request to DB
        $log = APILogs::create([
            'insurance_company_id' => $this->company_id,
            'method' => $method,
            'domain' => $this->host,
            'path' => $path,
            'request_header' => json_encode($request_options['headers']),
            'request' => $xml,
        ]);

        $result = HttpClient::curl($method, $url, $request_options);

        // Update the response
        APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => is_object($result->response) ? json_encode($result->response) : $result->response
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

    public function abort(string $message, int $code = 490) : ResponseData
    {
        return new ResponseData([
            'status' => false,
            'response' => $message,
            'code' => $code
        ]);
    }

    // Mapping
    private function getGender($data, $full = false) : string
    {
        if(!$full) {
            switch($data) {
                case 'M':
                case 'F': {
                    return $data;
                }
                case 'O': {
                    return 'C';
                }
            }
        } else {
            switch($data) {
                case 'M': {
                    return 'MALE';
                }
                case 'F': {
                    return 'FEMALE';
                }
                case 'O': {
                    return 'COMPANY';
                }
            }
        }
    }

    private function getMaritalStatusCode($marital_status) : int
    {
        $code = null;

        switch($marital_status) {
            case 'S': {
                $code = 0;
                break;
            }
            case 'M': {
                $code = 1;
                break;
            }
            case 'D': {
                $code = 2;
                break;
            }
            case 'O': {
                $code = 3;
                break;
            }
        }

        return $code;
    }

    private function getExtraCoverDescription(string $extra_cover_code) : string
    {
        $extra_cover_description = '';

        switch($extra_cover_code) {
            case '06': {
                $extra_cover_description = 'All Drivers';
                break;
            }
            case '05': {
                $extra_cover_description = 'Flood, Typhoon, Hurricane, Volcanic, Earthquake';
                break;
            }
            case '10': {
                $extra_cover_description = 'Additional Named Drivers';
                break;
            }
            case '04': {
                $extra_cover_description = 'L.L.P to Passengers';
                break;
            }
            case '25': {
                $extra_cover_description = 'Strike, Riot & Civil Commotion (S.R.C.C)';
                break;
            }
            case '89': {
                $extra_cover_description = 'Windscreen or Windows';
                break;
            }
            case '72': {
                $extra_cover_description = 'Legal Liability of Passengers (Act Of Negligence)';
                break;
            }
            case '111': {
                $extra_cover_description = 'Current Year NCD relief';
                break;
            };
        }

        return $extra_cover_description;
    }

    private function sortExtraCoverList(array $extra_cover_list) : array
    {
        foreach ($extra_cover_list as $_extra_cover) {
            $sequence = 99;

            switch ($_extra_cover->extra_cover_code) {
                case '06': { // All Drivers
                    $sequence = 1;
                    break;
                }
                case '10': {
                    $sequence = 1;
                    break;
                }
                case '89': { // Windscreen
                    $sequence = 2;
                    break;
                }
                case '04': { // Legal Liability to Passengers
                    $sequence = 3;
                    break;
                }
                case '05': {
                    $sequence = 4;
                    break;
                }
                case '25': { // Strike Riot & Civil Commotion
                    $sequence = 5;
                    break;
                }
                case '72': { // Tinted Film Screen
                    $sequence = 6;
                    break;
                }
                case '111': { // Current Year NCD relief
                    $sequence = 7;
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

    private function getStateCode(string $state)
    {
        $code = '';

        switch(ucwords(strtolower($state))) {
            case 'Johor':
            case 'Kedah':
            case 'Kelantan':
            case 'Melaka':
            case 'Pahang':
            case 'Perlis':
            case 'Perak':
            case 'Sabah':
            case 'Selangor':
            case 'Sarawak':
            case 'Terengganu': {
                $code = strtoupper($state);
                break;
            }
            case 'Negeri Sembilan': {
                $code = 'N.SEMBILAN';
                break;
            }
            case 'Wilayah Persekutuan Putrajaya': {
                $code = 'PUTRAJAYA';
                break;
            }
            case 'Pulau Pinang': {
                $code = 'P.PINANG';
                break;
            }
            case 'Wilayah Persekutuan Kuala Lumpur': {
                $code = 'W.PERSEKUTUAN';
                break;
            }
            case 'Wilayah Persekutuan Labuan': {
                $code = 'LABUAN';
                break;
            }
        }

        return $code;
    }

    private function getMake(int $make_code)
    {
        try {
            $json = File::get(storage_path('Motor/ism_make.json'));
            $make_listing = json_decode($json, true);

            foreach ($make_listing['makecode'] as $make) {
                if (trim($make['VEHMAKEMAJOR']) == $make_code) {
                    return trim($make['MAJORDESC']);
                }
            }
        } catch (FileNotFoundException $ex) {
            return $this->abort('Make mapping file not found!');
        }

        return '';
    }

    private function getModelDetails(string $nvic)
    {
        $model_details = null;

        try {
            $json = File::get(storage_path('Motor/ism_model.json'));
            $model_listing = json_decode($json, true);

            foreach ($model_listing['modelcode'] as $model) {
                if (trim($model['NVIC']) == $nvic) {
                    // Remove model from variant
                    $variant = str_replace(trim($model['MINORDESC']) . ' ', '', trim($model['REDBOOKDESC']));

                    $model_details = (object) [
                        'make' => $this->getMake(trim($model['VEHMAKEMAJOR'])),
                        'model' => trim($model['MINORDESC']),
                        'variant' => $variant,
                        'body_type_code' => VehicleBodyType::where('name', ucwords(strtolower(trim($model['BODYDESC']))))->pluck('id')->first(),
                        'body_type_description' => trim($model['BODYDESC']),
                    ];
                }
            }
        } catch (FileNotFoundException $ex) {
            return $this->abort('Model mapping file not found!');
        }

        return $model_details;
    }
}
