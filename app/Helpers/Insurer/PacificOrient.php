<?php

namespace App\Helpers\Insurer;

use App\DataTransferObjects\Motor\Response\PremiumResponse;
use App\DataTransferObjects\Motor\Response\ResponseData;
use App\DataTransferObjects\Motor\Response\VIXNCDResponse;
use App\Helpers\HttpClient;
use App\Interfaces\InsurerLibraryInterface;
use App\Models\APILogs;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PacificOrient implements InsurerLibraryInterface
{
    protected $token;
    protected $agent_code;
    protected $user_id;
    protected $host;

    // Settings
    private const PRODUCT = 'PC'; // Private Comprehensive
    // LLtP, SRCC, Windscreen, Tinted Flim, NCD Relief
    private const EXTRA_COVERAGE_LIST = ['04', '25', '89', '72', '111'];
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
    private const MAX_SUM_INSURED = 500000;

    private const SOAP_ACTION_DOMAIN = 'http://tempuri.org';

    public function __construct()
    {
        $this->agent_code = config('insurer.config.pno.agent_code');
        $this->user_id = config('insurer.config.pno.user_id');
        $this->host = config('insurer.config.pno.host');

        $this->token = $this->getToken();
    }

    public function vehicleDetails(object $input) : VIXNCDResponse
    {
        $data = (object) [
            'token' => $this->token,
            'id_number' => $input->id_number,
            'vehicle_number' => $input->vehicle_number
        ];

        $vix = $this->getVIXNCD($data);

        if(!$vix->status) {
            return $this->abort(__('api.api_error', [
                'company' => $input->company_name,
                'code' => 0
            ]));
        }

        $inception_date = Carbon::createFromFormat('Y-m-d', $vix->response->effDate);
        $expiry_date = $vix->response->expDate;
        $today = Carbon::today();

        // 1. Check Gap In Cover
        if($inception_date->lessThan($today)) {
            $gap_in_cover = $today->diffInDays($inception_date);

            if($gap_in_cover > self::ALLOWED_GAP_IN_COVER) {
                return $this->abort(__('api.gap_in_cover', ['days' => abs($gap_in_cover)]));
            }

            $inception_date = $today;
            $expiry_date = $today->addYear()->subDay()->format('Y-m-d');
        } else if($today->addMonths(2)->lessThan($inception_date)) {
            return $this->abort(__('api.earlier_renewal'), config('setting.response_codes.earlier_renewal'));
        }

        // 2. Check Sum Insured
        $sum_insured = formatNumber($vix->response->SumInsured, 0);
        if($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(__('api.sum_insured_referred_between', [
                'min_sum_insured' => self::MIN_SUM_INSURED,
                'max_sum_insured' => self::MAX_SUM_INSURED
            ]));
        }

        $variants = [];
        array_push($variants, [
            'nvic' => (string) $vix->response->NVIC,
            'sum_insured' => $vix->response->SumInsured,
            'variant' => '' 
        ]);

        return new VIXNCDResponse([
            'chassis_number' => $vix->response->ChassisNumber,
            'coverage' => 'Comprehensive',
            'engine_capacity' => $vix->response->EngineCC,
            'engine_number' => $vix->response->EngineNumber,
            'expiry_date' => Carbon::parse($vix->response->NextXDate)->format('Y-m-d'),
            'inception_date' => Carbon::parse($vix->response->NextEDate)->format('Y-m-d'),
            'make' => $vix->response->VehicleMake,
            'model' => $vix->response->VehicleModel,
            'manufacture_year' => $vix->response->YearManufactured,
            'max_sum_insured' => $sum_insured,
            'min_sum_insured' => $sum_insured,
            'sum_insured' => $sum_insured,
            'sum_insured_type' => 'Agreed Value',
            'ncd_percentage' => $vix->response->NCD,
            'seating_capacity' => $vix->response->SeatingCapacity,
            'variants' => $variants
        ]);
    }

    public function premiumDetails(object $input, $full_quote = false) : PremiumResponse
    {
        $vehicle = $input->vehicle ?? null;
        $ncd_amount = $basic_premium = $total_benefit_amount = $gross_premium = $sst_percent = $sst_amount = $stamp_duty = $excess_amount = $total_payable = $net_premium = 0;

        $id_number = $company_registration_number = '';
        switch($input->id_type) {
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
                    }
                }
            }

            if(empty($selected_variant)) {
                return $this->abort(__('api.variant_not_match'));
            }

            // Format Vehicle Object
            $vehicle = (object) [
                'coverage' => $vehicle_vix->response->coverage,
                'engine_capacity' => $vehicle_vix->response->engine_capacity,
                'expiry_date' => $vehicle_vix->response->expiry_date,
                'extra_attribute' => (object) [
                    'chassis_number' => $vehicle_vix->response->chassis_number,
                    'cover_type' => $vehicle_vix->response->cover_type,
                    'engine_number' => $vehicle_vix->response->engine_number,
                    'seating_capacity' => $vehicle_vix->response->seating_capacity,
                ],
                'inception_date' => $vehicle_vix->response->inception_date,
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
            ];

            $data = (object) [
                'gender' => $input->gender,
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

            $excess_amount = $premium->response->excessAmt;
            $net_premium = $premium->response->
            $ncd_amount = $premium->response->ncdAmt;
            $basic_premium = $premium->response->detariffPremium;
            $total_benefit_amount = 0;
            $gross_premium = $premium->response->grossPremium;
            $stamp_duty = $premium->response->stampDuty;
            $sst_amount = $premium->response->serviceTaxAmt;
            $total_payable = $premium->response->totalPremium;
            $sst_percent = ($sst_amount / $gross_premium) * 100;

            $extra_cover_list = [];
            foreach(self::EXTRA_COVERAGE_LIST as $_extra_cover_code) {
                $extra_cover = (object) [
                    'selected' => false,
                    'readonly' => false,
                    'extra_cover_code' => $_extra_cover_code,
                    'extra_cover_description' => $this->getExtraCoverDescription($_extra_cover_code),
                    'premium' => 0,
                    'sum_insured' => 0
                ];

                $sum_insured_amount = 0;

                switch($_extra_cover_code) {
                    case '89': {
                        $extra_cover->extra_cover_name = 'Windscreen or Windows';

                        // Options List for Windscreen
                        $option_list = (object) [
                            'name' => 'sum_insured',
                            'desctipion' => 'Sum Insured Amount',
                            'values' => generateExtraCoverSumInsured(500, 10000, 1000),
                            'any_value' => true,
                            'increment' => 100
                        ];

                        $extra_cover->option_list = $option_list;

                        // Default to RM1,000
                        $sum_insured_amount = $option_list->values[1];

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
        
        $data = (object) [
            'additional_driver' => $input->additional_driver,
            'email' => $input->email,
            'extra_cover' => $input->extra_cover,
            'gender' => $this->getGender($input->gender),
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'marital_status' => $this->getMaritalStatusCode($input->marital_status),
            'postcode' => $input->postcode,
            'region' => $input->region,
            'state' => $input->state,
            'vehicle' => $vehicle,
            'vehicle_number' => $input->vehicle_number,
        ];

        $premium = $this->getPremium($input);

        if(!$premium->status) {
            return $this->abort($premium->response);
        }

        if(!empty($premium->response->extraCoverage)) {
            foreach($premium->response->extraCoverage as $extra) {
                $total_benefit_amount += formatNumber($extra->totalPremium);
                $extra_cover->premium = formatNumber($extra->totalPremium);
            }
        }

        $response = new PremiumResponse([
            'act_premium' => formatNumber($premium->response->actPremium),
            'basic_premium' => formatNumber($premium->response->basicPremium),
            'basic_nett_premium' => formatNumber($premium->response->basicNettPremium),
            'detariff' => $premium->response->detariff,
            'detariff_premium' => formatNumber($premium->response->detariffPremium),
            'discount' => $premium->response->discount,
            'discount_amount' => formatNumber($premium->discountAmt),
            'excess_amount' => formatNumber($premium->response->excessAmt),
            'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
            'gross_premium' => formatNumber($premium->response->grossPremium),
            'loading' => formatNumber($premium->response->loadingAmt),
            'ncd' => formatNumber($premium->response->ncdAmt),
            'net_premium' => formatNumber($premium->response->basicNettPremium + $premium->response->serviceTaxAmt + $premium->response->stampDuty),
            'premium_before_rebate' => formatNumber($premium->response->premiumBeforeRebate),
            'rebate_amount' => formatNumber($premium->response->rebateAmt),
            'sst_amount' => formatNumber($premium->response->serviceTaxAmt),
            'sst_percent' => formatNumber(($premium->response->serviceTaxAmt / $premium->response->grossPremium) * 100),
            'stamp_duty' => formatNumber($premium->response->stampDuty),
            'tariff_premium' => formatNumber($premium->response->tariffPremium),
            'total_benefit_amount' => formatNumber($total_benefit_amount),
            'total_contribution' => formatNumber($premium->response->totalPremium),
            'total_payable' => formatNumber($premium->response->totalPremium),
            'request_id' => $premium->response->requestId,
        ]);

        if($full_quote) {
            // Revert to premium without extra covers
            $response->basic_premium = $basic_premium;
            $response->ncd = $ncd_amount;
            $response->gross_premium = $gross_premium;
            $response->stamp_duty = $stamp_duty;
            $response->sst_amount = $sst_amount;
            $response->sst_percent = $sst_percent;
            $response->total_benefit_amount = $total_benefit_amount;
            $response->total_payable = $total_payable;
        }

        return $response;
    }

    public function quotation(object $input) : PremiumResponse
    {
        $data = (object) [
            'additional_driver' => $input->additional_driver,
            'email' => $input->email,
            'extra_cover' => $input->extra_cover,
            'gender' => $this->getGender($input->gender),
            'id_type' => $input->id_type,
            'id_number' => $input->id_number,
            'marital_status' => $this->getMaritalStatusCode($input->marital_status),
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

        return new PremiumResponse([
            'basic_premium' => formatNumber($quotation->response->basicPremium),
            'excess_amount' => formatNumber($quotation->response->excessAmt),
            'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
            'gross_premium' => formatNumber($quotation->response->grossPremium),
            'ncd' => formatNumber($quotation->response->ncdAmt),
            'net_premium' => formatNumber($quotation->response->basicNettPremium + $quotation->response->serviceTaxAmt + $quotation->response->stampDuty),
            'sst_amount' => formatNumber($quotation->response->serviceTaxAmt),
            'sst_percent' => formatNumber(($quotation->response->serviceTaxAmt / $quotation->response->grossPremium) * 100),
            'stamp_duty' => formatNumber($quotation->response->stampDuty),
            'total_benefit_amount' => formatNumber($quotation->total_benefit_amount),
            'total_contribution' => formatNumber($quotation->response->totalPremium),
            'total_payable' => formatNumber($quotation->response->totalPremium),
            'request_id' => $quotation->response->requestId,
        ]);
    }

    public function submission(object $input) : ResponseData
    {
        // Get Extra Attribute
        $extra_attribute = json_decode($input->insurance->extra_attribute->value);
        
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
            'ref_no' => time(),
            'path' => $this->host . $path,
            'soap_action' => self::SOAP_ACTION_DOMAIN . '/' . $path,
        ];

        $xml = view('backend.xml.pacific.get_token')->with($data)->render();
        
        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response->respDescription);
        }

        return $result->response->TokenResp->accessToken;
    }

    private function getVIXNCD(object $input) : ResponseData
    {
        $path = 'getvehicleinfo/GetVehicleInfo.asmx';

        $xml = view('backend.xml.pacific.vehicle_details')->with($input)->render();

        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        // 1. Check for Error
        if(!empty($result->response->VehicleInfoResp->ErrorDesc)) {
            return $this->abort("P&O Error! {$result->response->VehicleInfoResp->ErrorDesc}");
        }

        return new ResponseData([
            'response' => $result->response->VehicleInfoResp,
        ]);
    }

    public function getPremium(object $input) : ResponseData
    {
        $path = 'poiapiv2/Insurance.svc';
        $token = $this->getToken();

        $data = [
            'all_rider' => 'N', // Default to No,
            'is_company' => $input->id_type === config('setting.id_type.company_registration_no') ? 'Y' : 'N',
            'coverage' => self::COVER_TYPE,
            'effective_date' => $input,
            'expiry_date' => $input,
            'extra_cover' => $input->extra_cover,
            'gender' => $this->getGender($input->gender),
            'age' => $input->age,
            'marital_status' => $input->marital_status,
            'ncd_percentage' => $input->ncd_percentage,
            'additional_driver_count' => count($input->additional_driver),
            'id_number' => $input->id_number,
            'hire_purchase' => 'N',
            'postcode' => $input->postcode,
            'reference_number' => Str::uuid(),
            'sum_insured' => $input->vehicle->sum_insured,
            'token' => $token,
            'nvic' => $input->vehicle->nvic,
            'vehicle_number' => $input->vehicle_number,
            'vehicle_type' => $input->vehicle->seating_capacity <= 5 ? '2-01' : '2-06'
        ];

        $xml = view('backend.xml.pacific.premium')->with($data)-> render();

        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }

        return new ResponseData([
            'response' => $result->response->PremiumRequestResponse,
        ]);
    }

    public function issueCoverNote(object $input) : ResponseData
    {
        $path = 'poiapiv2/Insurance.svc';
        $token = $this->getToken();

        $data = (object) [
            'address_one' => $input->insurance->address->address_one,
            'address_two' => $input->insurance->address->address_two,
            'anti_theft' => self::ANTI_THEFT_DEVICE,
            'chassis_number' => $input->vehicle->extra_attribute->chassis_number,
            'city' => $input->insurance->address->city,
            'commission_amount' => formatNumber($input->premium_details->gross_premium * 0.1),
            'commission_rate' => 10,
            'country_code' => self::COUNTRY,
            'cover_region_code' => substr(strtoupper($input->region), 1),
            'cover_note_date' => $input->insurance->quotation_date,
            'cover_note_effective_date' => $input->vehicle->inception_date,
            'cover_note_expiry_date' => Carbon::parse($input->vehicle->inception_date)->addYear()->subDay()->format('Y-m-d'),
            'customer_category' => self::CUSTOMER_CATEGORY,
            'customer_name' => $input->insurance->holder->name,
            'date_of_birth' => $input->insurance->holder->date_of_birth,
            'email' => $input->insurance->holder->email,
            'email_aggregator' => 'instapol@my.howdengroup.com',
            'engine_number' => $input->vehicle->extra_attribute->engine_number,
            'extra_coverage' => $input->extra_cover,
            'garage_code' => self::GARAGE_CODE,
            'id_number' => $input->insurance->holder->id_number,
            'import_type' => self::IMPORT_TYPE,
            'insured_age' => getAgeFromIC($input->insurance->holder->id_number),
            'insured_gender' => $this->getGender($input->insurance->holder->gender),
            'insured_name' => $input->insurance->holder->name,
            'logbook_number' => 'A001',
            'marital_status' => $input->insurance->holder->marital_status,
            'ncd_amount' => $input->insurance_motor->ncd_amount,
            'ncd_percentage' => $input->vehicle->ncd_percentage,
            'nvic' => $input->vehicle->nvic,
            'named_driver' => $input->additional_driver,
            'occupation' => self::OCCUPATION,
            'permitted_driver' => self::PERMITTED_DRIVERS,
            'phone_number' => $input->insurance->holder->phone_code . $input->insurance->holder->phone_number,
            'postcode' => $input->insurance->address->postcode,
            'reference_number' => Str::uuid(),
            'safety_feature_code' => self::SAFETY_FEATURES,
            'seat_capacity' => $input->vehicle->extra_attribute->seating_capacity,
            'state_code' => $this->getStateCode($input->insurance->address->state),
            'sum_insured' => $input->vehicle->sum_insured,
            'token' => $token,
            'type_of_cover' => self::COVER_TYPE,
            'vehicle_number' => $input->vehicle_number,
            'year_make' => $input->vehicle->manufacturing_year,
            'request_id' => $input->vehicle->extra_attribute->request_id,
            'premium_details' => $input->premium_details
        ];

        $xml = view('backend.xml.pacific.cover_note_submission')->with($data)->render();

        $result = $this->cURL($path, $xml);

        if(!$result->status) {
            return $this->abort($result->response);
        }
    }

    public function cURL(string $path, string $xml, string $method = 'POST', array $header = []) : ResponseData
    {
        // Concatenate URL
        $url = $this->host . $path;

        // Check XML Error
        libxml_use_internal_errors(true);

        // Construct API Request
        $request_options = [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/xml',
                'Accept' => 'text/xml',
                'SOAPAction' => self::SOAP_ACTION_DOMAIN . $path
            ],
            'body' => $xml
        ];

        // Log the Request to DB
        $log = APILogs::create([
            'insurance_company_id' => config('insurer.config.pno.id'), // Hardcoded Insurance Company ID
            'method' => $method,
            'domain' => $this->host,
            'path' => $path,
            'request_header' => json_encode($request_options['headers']),
            'request' => json_encode($xml),
        ]);

        $result = HttpClient::curl($method, $url, $request_options);
        $code = null;

        // Update the response
        APILogs::find($log->id)
            ->update([
                'response_header' => json_encode($result->response_header),
                'response' => json_encode($result->response)
            ]);

        if($result->status) {
            $response = simplexml_load_string($result->response);

            if($response === false) {
                return $this->abort(trans('api.xml_error'));
            }

            if(strpos($path, 'GetAccessToken') !== false) {
                $response->registerXPathNamespace('res', 'http://schemas.datacontract.org/2004/07/PO.TravelAssurance');
            } else {
                $response->registerXPathNamespace('res', 'http://schemas.datacontract.org/2004/07/PO.Web.API');
            }

            if((int) $response->xpath('//res:respCode') === 0) {
                $code = (string) $response->xpath('//res:respCode')[0];

                return $this->abort(trans('api.api_error', [
                    'code' => $code,
                    'company' => $this->company,
                    'message' => $response->xpath('//res;respDescription')[0],
                ]));
            }

            $response = $response->xpath('//res:Body')[0];
        } else {
            $message = !empty($result->response) ? $result->response : trans('api.empty_response', ['company' => $this->company]);

            return $this->abort($message);
        }

        return new ResponseData([
            'status' => $result->status,
            'response' => $response,
            'code' => $code
        ]);
    }

    public function abort(string $message, int $code = 500) : ResponseData
    {
        return new ResponseData([
            'status' => false,
            'response' => $message,
            'code' => $code
        ]);
    }

    // Mapping
    private function getGender($data) : string
    {
        $gender = '';

        switch($data) {
            case 'F': {
                $gender = 'FEMALE';
                break;
            }
            case 'O': {
                $gender = 'COMPANY';
                break;
            }
            default: {
                $gender = 'MALE';
            }
        }

        return $gender;
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
        $extra_cover_name = '';

        switch($extra_cover_code) {
            case '04': {
                $extra_cover_name = 'L.L.P to Passengers';
                break;
            }
            case '25': {
                $extra_cover_name = 'Strike, Riot & Civil Commotion (S.R.C.C)';
                break;
            }
            case '89': {
                $extra_cover_name = 'Windscreen or Windows';
                break;
            }
            case '72': {
                $extra_cover_name = 'Tinted Film Screen';
                break;
            }
            case '111': {
                $extra_cover_name = 'Current Year NCD relief';
                break;
            };
        }

        return $extra_cover_name;
    }

    private function sortExtraCoverList(array $extra_cover_list) : array
    {
        foreach ($extra_cover_list as $_extra_cover) {
            $sequence = 99;

            switch ($_extra_cover->extra_cover_code) {
                case '89': { // Windscreen
                    $sequence = 1;
                    break;
                }
                case '04': { // Legal Liability to Passengers
                    $sequence = 2;
                    break;
                }
                case '25': { // Strike Riot & Civil Commotion
                    $sequence = 3;
                    break;
                }
                case '72': { // Tinted Film Screen
                    $sequence = 4;
                    break;
                }
                case '111': { // Current Year NCD relief
                    $sequence = 5;
                    break;
                }
            }

            $_extra_cover->sequence = $sequence;

            $sorted = array_values(Arr::sort($extra_cover_list, function ($value) {
                return $value->sequence;
            }));
    
            return $sorted;
        }
    }

    private function getStateCode(string $state)
    {
        $code = '';

        switch($state) {
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
}