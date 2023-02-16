<?php

namespace App\Http\Controllers\API;

use App\DataTransferObjects\Motor\APIData;
use App\DataTransferObjects\Motor\Response\FullQuoteResponse;
use App\DataTransferObjects\Motor\Response\QuoteResponse;
use App\DataTransferObjects\Motor\Response\QuotationResponse;
use App\DataTransferObjects\Motor\Response\RoadtaxResponse;
use App\DataTransferObjects\Motor\Response\SubmitCoverNoteResponse;
use App\DataTransferObjects\Motor\Vehicle;
use App\DataTransferObjects\Motor\VehicleVariantData;
use App\Helpers\Insurer\BerjayaSompo;
use App\Helpers\Insurer\Liberty;
use App\Helpers\Insurer\PacificOrient;
use App\Helpers\Insurer\AmGeneral;
use App\Helpers\Insurer\Allianz;
use App\Helpers\Insurer\AIG;
use App\Helpers\Insurer\Zurich;
use App\Helpers\Insurer\ZurichTakaful;
use App\Http\Controllers\Controller;
use App\Interfaces\MotorAPIInterface;
use App\Models\InsuranceAddress;
use App\Models\InsurancePremium;
use App\Models\InsuranceRemark;
use App\Models\Motor\Insurance;
use App\Models\Motor\InsuranceExtraAttribute;
use App\Models\Motor\InsuranceExtraCover;
use App\Models\Motor\InsuranceHolder;
use App\Models\Motor\InsuranceMotor;
use App\Models\Motor\InsuranceMotorDriver;
use App\Models\Motor\InsuranceMotorRoadtax;
use App\Models\Motor\InsurancePromo;
use App\Models\Motor\Product;
use App\Models\Motor\Quotation;
use App\Models\Motor\RoadtaxDeliveryType;
use App\Models\Motor\VehicleBodyType;
use App\Models\Postcode;
use App\Models\RoadTaxMatrix;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MotorAPIController extends Controller implements MotorAPIInterface
{
    /** @return VehicleVariantData|Response */
    public function getVehicleDetails(Request $request)
    {
        // Create a Log Record
        Log::info("[API/GetVehicleDetails] Received Request: " . json_encode($request->all()));

        // Get State Details with Postcode
        $postcode = $this->getPostcodeDetails($request->postcode);

        $data = new APIData([
            'id_type' => intval($request->id_type),
            'id_number' => $request->id_number,
            'vehicle_number' => strtoupper($request->vehicle_number),
            'postcode' => $postcode->postcode,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'region' => $postcode->state->region,
            'state' => $postcode->state->name,
        ]);

        try {
            $insurer = $this->getInsurerClass($request->product_id);
            $result = $insurer->vehicleDetails($data);
            Log::info("[API/GetVehicleDetails] VIXNCD Response: " . json_encode($result));

            if(!$result->status) {
                Quotation::where('vehicle_number', strtoupper($request->vehicle_number))
                    ->where('updated_at', '>=', Carbon::now()->subMonth())
                    ->where('updated_at', '<', Carbon::now())
                    ->where('active', Quotation::ACTIVE)
                    ->update([
                        'remarks' => $result->response
                    ]);

                return $this->abort($result->response, $result->code);
            }         
    
            // Include Insurer Details in Response
            $product = Product::with(['insurance_company'])
                ->where('id', $request->product_id)
                ->first();
            $result->response->insurer = $product->insurance_company->name;
            $result->response->product_name = $product->name;
    
            $data = new VehicleVariantData([
                'insurer' => $result->response->insurer,
                'product_name' => $result->response->product_name,
                'vehicle_number' => $result->response->vehicle_number,
                'make' => $result->response->make ?? '',
                'model' => $result->response->model ?? '',
                'engine_capacity' => $result->response->engine_capacity,
                'manufacture_year' => $result->response->manufacture_year,
                'ncd_percentage' => $result->response->ncd_percentage,
                'coverage' => $result->response->coverage,
                'inception_date' => $result->response->inception_date,
                'expiry_date' => $result->response->expiry_date,
                'sum_insured' => $result->response->sum_insured,
                'min_sum_insured' => $result->response->max_sum_insured,
                'max_sum_insured' => $result->response->min_sum_insured,
                'variants' => $result->response->variants,
                'extra_attribute' => (object) [
                    'make_code' => $result->response->make_code,
                    'model_code' => $result->response->model_code,
                    'vehicle_use_code' => $result->response->vehicle_use_code ?? '',
                    'vehicle_type_code' => $result->response->vehicle_type_code ?? '',
                    'class_code' => $result->response->class_code ?? '',
                    'coverage_code' => $result->response->coverage,
                    'engine_number' => $result->response->engine_number,
                    'chassis_number' => $result->response->chassis_number,
                    'seating_capacity' => $result->response->seating_capacity,
                ]
            ]);

            return $data->all();
        } catch(Exception $ex) {
            Log::error("[API/GetVehicleDetails] An error occurred. {$ex->getMessage()}");
            
            return $this->abort($ex->getMessage());
        }
    }

    public function getQuote(Request $request, string $quote_type = '')
    {
        $full_quote = $quote_type === 'full';
        $motor = toObject($request->motor);

        // Get State Details with Postcode
        $postcode = $this->getPostcodeDetails($motor->postcode);
        
        // Get Product Details
        $product = $this->getProduct($request->product_id);

        // Get Vehicle Body Type Details
        if(!empty($motor->vehicle_body_type)) {
            $vehicle_body_type_id = VehicleBodyType::where('name', $motor->vehicle_body_type)->pluck('id');
        }
        
        switch($motor->policy_holder->id_type) {
            case 1: {
                $age = getAgeFromIC($motor->policy_holder->id_number);

                break;
            }
            case 4: {
                $age = 0;

                break;
            }
        }

        $data = new APIData([
            'age' => $age,
            'id_type' => $motor->policy_holder->id_type,
            'id_number' => $motor->policy_holder->id_number,
            'vehicle_number' => strtoupper($motor->vehicle_number),
            'postcode' => $postcode->postcode,
            'email' => $motor->policy_holder->email,
            'region' => $postcode->state->region,
            'state' => $postcode->state->name,
            'insurer_id' => $product->insurance_company->id,
            'insurer_name' => $product->insurance_company->name,
            'product_id' => $product->id,
            'gender' => $motor->policy_holder->gender,
            'marital_status' => $motor->policy_holder->marital_status,
            'nvic' => $motor->vehicle->nvic,
            'vehicle' => new Vehicle([
                'make' => $motor->vehicle->make,
                'model' => $motor->vehicle->model,
                'nvic' => $motor->vehicle->nvic ?? $motor->variants[0]->nvic,
                'variant' => $motor->vehicle->variant ?? $motor->variants[0]->variant,
                'engine_capacity' => $motor->vehicle->engine_capacity,
                'manufacture_year' => $motor->vehicle->manufacture_year,
                'ncd_percentage' => $motor->vehicle->ncd_percentage,
                'coverage' => $motor->vehicle->coverage,
                'inception_date' => Carbon::parse($motor->vehicle->inception_date)->format('Y-m-d'),
                'expiry_date' => empty($motor->vehicle->expiry_date) ? Carbon::parse($motor->vehicle->inception_date)->addYear()->subDay()->format('Y-m-d') : Carbon::parse($motor->vehicle->expiry_date)->format('Y-m-d'),
                'sum_insured_type' => $motor->vehicle->sum_insured_type ?? '',
                'sum_insured' => $motor->vehicle->sum_insured ?? 0.00,
                'min_sum_insured' => $motor->vehicle->min_sum_insured ?? 0.00,
                'max_sum_insured' => $motor->vehicle->max_sum_insured ?? 0.00,
                'extra_attribute' => (object) [
                    'class_code' => $motor->vehicle->extra_attribute->class_code ?? '',
                    'coverage_code' => $motor->vehicle->extra_attribute->coverage_code ?? '',
                    'make_code' => $motor->vehicle->extra_attribute->make_code ?? '',
                    'model_code' => $motor->vehicle->extra_attribute->model_code ?? '',
                    'engine_number' => $motor->vehicle->extra_attribute->engine_number,
                    'chassis_number' => $motor->vehicle->extra_attribute->chassis_number,
                    'vehicle_use_code' => $motor->vehicle->extra_attribute->vehicle_use_code ?? '',
                    'seating_capacity' => $motor->vehicle->extra_attribute->seating_capacity,
                    'body_type_code' => $motor->vehicle->extra_attribute->body_type_code ?? '',
                    'body_type_description' => $motor->vehicle->extra_attribute->body_type_description ?? '',
                    'cover_type' => $motor->vehicle->extra_attribute->cover_type ?? '',
                    'liberty_model_code' => $motor->vehicle->extra_attribute->liberty_model_code ?? '',
                    'vehicle_type_code' => $motor->vehicle->extra_attribute->vehicle_type_code ?? '',
                ]
            ]),
            'extra_cover' => toObject($request->extra_cover ?? []),
            'additional_driver' => toObject($request->additional_driver ?? []),
            'vehicle_body_type' => $vehicle_body_type_id ?? null,
            'phone_number' => $motor->policy_holder->phone_number,
            'occupation' => strtoupper($motor->policy_holder->occupation ?? '')
        ]);

        Log::info('[GetQuote] Received Request: ' . json_encode($data));

        $insurer = $this->getInsurerClass($product->id);
        $result = $insurer->premiumDetails($data, $full_quote);

        if(!$result->status) {
            return abort($result->code, $result->response);
        }

        if($full_quote) {
            $quote = new FullQuoteResponse([
                'company' => $product->insurance_company->name,
                'product_name' => $product->name,
                'basic_premium' => $result->response->basic_premium,
                'ncd_amount' => $result->response->ncd_amount,
                'total_benefit_amount' => $result->response->total_benefit_amount,
                'gross_premium' => $result->response->gross_premium,
                'sst_percent' => $result->response->sst_percent,
                'sst_amount' => $result->response->sst_amount,
                'stamp_duty' => $result->response->stamp_duty,
                'sum_insured' => $result->response->sum_insured,
                'min_sum_insured' => $result->response->min_sum_insured,
                'max_sum_insured' => $result->response->max_sum_insured,
                'sum_insured_type' => $result->response->sum_insured_type,
                'excess_amount' => $result->response->excess_amount,
                'loading' => $result->response->loading,
                'total_payable' => $result->response->total_payable,
                'net_premium' => $result->response->net_premium,
                'extra_cover' => $result->response->extra_cover,
                'named_drivers_needed' => $result->response->named_drivers_needed,
                'vehicle' => $result->response->vehicle
            ]);
        } else {
            $quote = new QuoteResponse([
                'company' => $product->insurance_company->name,
                'product_name' => $product->name,
                'roadtax' => $result->response->roadtax ?? [],
                'basic_premium' => $result->response->basic_premium,
                'ncd_amount' => $result->response->ncd_amount,
                'total_benefit_amount' => $result->response->total_benefit_amount,
                'gross_premium' => $result->response->gross_premium,
                'sst_percent' => $result->response->sst_percent,
                'sst_amount' => $result->response->sst_amount,
                'stamp_duty' => $result->response->stamp_duty,
                'sum_insured' => $result->response->sum_insured,
                'excess_amount' => $result->response->excess_amount,
                'loading' => $result->response->loading,
                'total_payable' => $result->response->total_payable,
                'extra_cover' => $result->response->extra_cover,
            ]);
        }

        return $quote->all();
    }

    /** @return QuotationResponse|Response */
    public function createQuotation(Request $request)
    {
        Log::info("[API/CreateQuotation] Received Request: " . json_encode($request->input()));
        $motor = toObject($request->motor);

        // Get State Details with Postcode
        $postcode = $this->getPostcodeDetails($motor->postcode);

        // Get Product Details
        $product = $this->getProduct($motor->product_id);

        // Get Vehicle Body Type Details
        if(!empty($request->vehicle_body_type)) {
            $vehicle_body_type_id = VehicleBodyType::where('name', $request->vehicle_body_type)
                ->get()
                ->id;
        }

        // Get the Variant to Construct DTO
        $motor->vehicle->variant = array_values(array_filter($motor->variants, function($variant) use($motor) {
            return $variant->nvic === $motor->vehicle->nvic;
        }))[0]->variant;

        $input = new APIData([
            'id_type' => $motor->policy_holder->id_type,
            'id_number' => $motor->policy_holder->id_number,
            'vehicle_number' => strtoupper($motor->vehicle_number),
            'postcode' => $motor->postcode,
            'email' => $motor->policy_holder->email,
            'region' => $postcode->state->region,
            'state' => strtoupper($postcode->state->name),
            'product_id' => $product->id,
            'gender' => $motor->policy_holder->gender,
            'marital_status' => $motor->policy_holder->marital_status,
            'vehicle' => new Vehicle((array) $motor->vehicle),
            'extra_cover' => toObject($motor->selected_extra_coverage ?? []),
            'additional_driver' => toObject($motor->additional_driver ?? []),
            'vehicle_body_type' => $vehicle_body_type_id ?? null,
            'name' => strtoupper($motor->policy_holder->name),
            'date_of_birth' => $motor->policy_holder->date_of_birth,
            'age' => $motor->policy_holder->id_type === config('setting.id_type.nric_no') ? getAgeFromIC($motor->policy_holder->id_number) : null,
            'phone_code' => '60',
            'phone_number' => $motor->policy_holder->phone_number,
            'address_one' => strtoupper($motor->policy_holder->address_1),
            'address_two' => strtoupper($motor->policy_holder->address_2),
            'city' => strtoupper($motor->policy_holder->city),
            'occupation' => strtoupper($motor->policy_holder->occupation ?? ''),
            'promo' => $motor->promo ?? []
        ]);

        // Remove '0' from phone number if exists
        if(substr($input->phone_number, 0, 1) == '0') {
            $input->phone_number = substr($input->phone_number, 1);
        }

        $insurer_class = $this->getInsurerClass($product->id);
        $result = $insurer_class->quotation($input);

        if(!$result->status) {
            return $this->abort($result->response, $result->code);
        }

        $quotation = $result->response;
        $insurance_code = '';

        // Re-calculate Premium
        $total_payable = $quotation->total_payable;
        if(!empty($motor->premium->discounted_amount)) {
            $total_payable -= floatval($motor->premium->discounted_amount);
        }

        if(!empty($motor->premium->roadtax)) {
            $total_payable += floatval($motor->premium->roadtax);
        }

        // Check if the user exists in the system
        $user = User::where('email', $input->email)->first();

        if(empty($user->id)) {
            // Create user account
            $user = User::create([
                'name' => strtoupper($input->name),
                'id_number' => $input->id_number,
                'email' => $input->email,
                'password' => Crypt::encryptString(Str::random(12)),
            ]);
        }

        try {
            DB::beginTransaction();
            
            // 1. Check if a record exists with vehicle number
            $insurance_motor = InsuranceMotor::where('vehicle_number', $input->vehicle_number)->first();

            $insurance_id = null;
            if(!empty($insurance_motor)) {
                if(Insurance::find($insurance_motor->insurance_id)->exists()) {
                    $insurance_id = $insurance_motor->insurance_id;
                }
            }

            // 2a. Update or Insert to Insurance table
            $insurance = Insurance::updateOrCreate([
                'id' => $insurance_id,
                'insurance_status' => Insurance::STATUS_NEW_QUOTATION,
                'channel' => 'online',
            ], [
                'product_id' => $product->id,
                'customer_id' => $user->id,
                'referrer' => $request->referrer,
                'inception_date' => Carbon::parse($input->vehicle->inception_date) ->format('Y-m-d'),
                'expiry_date' => Carbon::parse($input->vehicle->expiry_date) ->format('Y-m-d'),
                'amount' => $total_payable,
                'quotation_date' => Carbon::now()->format('Y-m-d'),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 2b. Generate & Update Insurance Code
            $insurance_code = generateInsuranceCode(config('setting.howden.short_code'), $product->id, $insurance->id);
            $insurance->update(['insurance_code' => $insurance_code]);

            // 3. Update or Insert to Insurance Policy Holder table
            InsuranceHolder::updateOrCreate([
                'insurance_id' => $insurance->id
            ], [
                'name' => strtoupper($input->name),
                'id_type_id' => $input->id_type,
                'id_number' => $input->id_number,
                'nationality' => 'MYS',
                'date_of_birth' => $input->date_of_birth,
                'age' => $input->age,
                'gender' => $input->gender,
                'phone_code' => $input->phone_code,
                'phone_number' => $input->phone_number,
                'email_address' => $input->email,
                'occupation' => strtoupper($input->occupation),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 4. Update or Insert to Insurance Address Table
            InsuranceAddress::updateOrCreate([
                'insurance_id' => $insurance->id
            ], [
                'address_one' => strtoupper($input->address_one),
                'address_two' => strtoupper($input->address_two),
                'city' => strtoupper($input->city),
                'state' => strtoupper($input->state),
                'postcode' => $input->postcode,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 5. Update or Insert to Insurance Motor Table
            $insurance_motor = InsuranceMotor::updateOrCreate([
                'insurance_id' => $insurance->id
            ], [
                'vehicle_state_id' => $postcode->state->id,
                'vehicle_number' => strtoupper($input->vehicle_number),
                'chassis_number' => $input->vehicle->extra_attribute->chassis_number,
                'engine_number' => $input->vehicle->extra_attribute->engine_number,
                'make' => $input->vehicle->make,
                'model' => $input->vehicle->model,
                'seating_capacity' => $input->vehicle->extra_attribute->seating_capacity,
                'engine_capacity' => $input->vehicle->engine_capacity,
                'manufactured_year' => $input->vehicle->manufacture_year,
                'market_value' => $input->vehicle->sum_insured,
                'sum_insured_type' => $input->vehicle->sum_insured_type,
                'nvic' => $input->vehicle->nvic,
                'variant' => $input->vehicle->variant,
                'ncd_percentage' => $input->vehicle->ncd_percentage,
                'ncd_amount' => $quotation->ncd_amount,
                'previous_ncd_percentage' => $input->vehicle->extra_attribute->previous_ncd ?? 0.00,
                'next_ncd_percentage' => $input->vehicle->extra_attribute->next_ncd ?? 0.00,
                'previous_inception_date' => Carbon::parse($input->vehicle->expiry_date)->subYear()->addDay(),
                'previous_expiry_date' => Carbon::parse($input->vehicle->expiry_date)->subYear()->addDays(2),
                'previous_policy_expiry' => Carbon::parse($input->vehicle->expiry_date)->subYear()->addDay(2),
                'disabled' => 'N',
                'marital_status' => $input->marital_status,
                'driving_experience' => $input->age - 18 > 0 ? $input->age - 18 : 1,
                'loading' => $quotation->loading,
                'number_of_drivers' => count($input->additional_driver),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 6a. Delete Existing Record
            InsuranceExtraCover::where('insurance_id', $insurance->id)->delete();
            // 6b. Update or Insert to Insurance Extra Cover Table
            foreach($input->extra_cover as $extra_cover) {
                InsuranceExtraCover::create([
                    'insurance_id' => $insurance->id,
                    'code' => $extra_cover->extra_cover_code,
                    'description' => $extra_cover->extra_cover_description,
                    'sum_insured' => $extra_cover->sum_insured,
                    'amount' => array_values(array_filter($quotation->extra_cover, function($item) use($extra_cover) {
                        return $item->extra_cover_code === $extra_cover->extra_cover_code;
                    }))[0]->premium,
                ]);
            }

            // 7. Update or Insert to Insurance Motor PA Table
            // 8. Update or Insert to Insurance Motor Roadtax Table
            if(!empty($motor->roadtax)) {
                $region = Postcode::with(['state'])
                    ->where('postcode', $input->postcode)
                    ->first()
                    ->state
                    ->region;

                if($region === 'East') {
                    $delivery_type = RoadtaxDeliveryType::where('description', RoadtaxDeliveryType::EM)->first();
                } else {
                    if((int) $input->postcode >= 40000 && (int) $input->postcode <= 68100) {
                        $delivery_type = RoadtaxDeliveryType::where('description', RoadtaxDeliveryType::KV)->first();
                    } else {
                        $delivery_type = RoadtaxDeliveryType::where('description', RoadtaxDeliveryType::OTHERS)->first();
                    }
                }

                InsuranceMotorRoadtax::updateOrCreate([
                    'insurance_motor_id' => $insurance_motor->id
                ], [
                    'roadtax_delivery_region_id' => $delivery_type->id,
                    'roadtax_renewal_fee' => $motor->roadtax->roadtax_price,
                    'myeg_fee' => $motor->roadtax->myeg_fee,
                    'e_service_fee' => $motor->roadtax->eservice_fee,
                    'service_tax' => $motor->roadtax->sst,
                    'recipient_name' => strtoupper($motor->roadtax->recipient_name),
                    'recipient_phone_number' => $motor->roadtax->recipient_phone_number,
                    'recipient_address_one' => strtoupper($motor->roadtax->address_one),
                    'recipient_address_two' => strtoupper($motor->roadtax->address_two),
                    'recipient_postcode' => $motor->roadtax->postcode,
                    'recipient_city' => strtoupper($motor->roadtax->city),
                    'recipient_state' => strtoupper($motor->roadtax->state),
                ]);
            }

            // 9. Delete Existing Records
            InsuranceMotorDriver::where('insurance_motor_id', $insurance_motor->id)->delete();
            // 9a. Update or Insert to Insurance Motor Driver Table
            foreach($input->additional_driver as $additional_driver) {
                InsuranceMotorDriver::create([
                    'insurance_motor_id' => $insurance_motor->id,
                    'name' => $additional_driver->name,
                    'id_number' => $additional_driver->id_number,
                    'relationship_id' => $additional_driver->relationship
                ]);
            }

            // 10. Update or Insert to Insurance Premium Table
            InsurancePremium::updateOrCreate([
                'insurance_id' => $insurance->id
            ], [
                'basic_premium' => number_format($quotation->basic_premium, 2, '.', ''),
                'gross_premium' => number_format($quotation->gross_premium, 2, '.', ''),
                'act_premium' => number_format($quotation->act_premium ?? 0, 2, '.', ''),
                'net_premium' => number_format($quotation->net_premium, 2, '.', ''),
                'service_tax_amount' => number_format($quotation->sst_amount, 2, '.', ''),
                'stamp_duty' => number_format($quotation->stamp_duty, 2, '.', ''),
                'total_premium' => number_format($quotation->total_payable, 2, '.', ''),
                'remarks' => '',
            ]);

            // 11. Insert to Insurance Remarks Table
            InsuranceRemark::create([
                'insurance_id' => $insurance->id,
                'remark' => "{$product->insurance_company->name} Quotation generated successfully. (Insurance Code: {$insurance_code})"
            ]);

            // 12. Delete Insurance Promo Table
            if(!empty($input->promo)) {
                InsurancePromo::where([
                    'insurance_id' => $insurance->id
                ])->delete();

                InsurancePromo::create([
                    'insurance_id' => $insurance->id,
                    'promo_id' => $input->promo->id,
                    'discount_amount' => $motor->premium->discount_amount,
                ]);
            }

            // 13. Update or Insert to Insurance Extra Attributes Table
            /// a. Update the value
            if(!empty($quotation->extra_attribute)) {
                foreach($quotation->extra_attribute as $key => $value) {
                    $input->vehicle->extra_attribute->{$key} = $value;
                }
            }

            /// b. Include the Request ID
            if(!empty($quotation->request_id)) {
                $input->vehicle->extra_attribute->request_id = $quotation->request_id;
            }

            InsuranceExtraAttribute::updateOrCreate([
                'insurance_id' => $insurance->id
            ], [
                'value' => json_encode($input->vehicle->extra_attribute)
            ]);

            DB::commit();
            Log::info("[API/CreateQuotation] Executed Sucessfully.");

            $quotation->insurance_company = $product->insurance_company->name;
            $quotation->product_name = $product->name;

            $quote = new QuotationResponse([
                'insurance_code' => $insurance_code,
                'quotation' => json_decode(json_encode($quotation), true),
            ]);

            return $quote->all();
        } catch (Exception $ex) {
            Log::error("[API/CreateQuotation] An Error Encountered. {$ex->getMessage()}");
            DB::rollBack();
        }
    }

    public function calculateRoadtax(Request $request)
    {
        // 1. Get Region from Postcode
        $postcode_details = $this->getPostcodeDetails($request->postcode);

        if(empty($postcode_details)) {
            return $this->abort("Unable to get postcode details for {$request->postcode}");
        }

        $region = $postcode_details->state->region;

        // 2. Get Roadtax Matrix
        $roadtax = RoadTaxMatrix::whereRaw('? BETWEEN engine_capacity_from AND engine_capacity_to', $request->engine_capacity)
            ->where('region', $region)
            ->where(function($query) use($request) {
                $query->where('registration_type', $request->id_type === config('setting.id_type.nric_no') ? 'Individual' : 'Company');
                $query->orWhere('registration_type', NULL);
            })
            ->where('saloon', $request->body_type === 'saloon')
            ->first();

        // 3. Get Delivery Fee
        if($region === 'East') {
            $delivery_fee = RoadtaxDeliveryType::where('description', RoadtaxDeliveryType::EM)->first();
        } else {
            if((int) $request->postcode >= 40000 && (int) $request->postcode <= 68100) {
                $delivery_fee = RoadtaxDeliveryType::where('description', RoadtaxDeliveryType::KV)->first();
            } else {
                $delivery_fee = RoadtaxDeliveryType::where('description', RoadtaxDeliveryType::OTHERS)->first();
            }
        }
        
        // Collection Method - MyJPJ App
        if(!$request->delivery) {
            $delivery_fee->amount = 0;
        }

        // 4. Calculation
        $roadtax_price = formatNumber($roadtax->base_rate);
        if(!empty($roadtax->progressive_rate)) {
            $additional_engine_capacity = abs(intval($request->engine_capacity) - (intval($roadtax->engine_capacity_from) - 1));

            $roadtax_price += formatNumber($additional_engine_capacity) * formatNumber($roadtax->progressive_rate);
        }

        $delivery = formatNumber($delivery_fee->processing_fee) + formatNumber($delivery_fee->amount);
        $delivery += formatNumber($delivery * 0.06);
        $e_service_fee = (formatNumber($roadtax_price) + formatNumber($delivery_fee->processing_fee)) * 0.02;
        $sst = $e_service_fee * 0.06;
        $total = formatNumber($roadtax_price) + formatNumber($e_service_fee) + formatNumber($delivery) + formatNumber($sst);

        $response = new RoadtaxResponse([
            'roadtax_price' => formatNumber($roadtax_price),
            'myeg_fee' => formatNumber($delivery),
            'eservice_fee' => formatNumber($e_service_fee),
            'sst' => formatNumber($sst),
            'total' => formatNumber($total)
        ]);

        return $response->all();
    }

    public function submitCoverNote(Request $request) : SubmitCoverNoteResponse
    {
        // Get Insurance Details
        $insurance = Insurance::with([
                'product',
                'extra_cover',
                'holder',
                'motor',
                'address',
                'promo',
                'remark'
            ])
            ->where('insurance_code', $request->insurance_code)
            ->where('insurance_status', Insurance::STATUS_PAYMENT_ACCEPTED)
            ->first();

        // Get State Details with Postcode
        $postcode = $this->getPostcodeDetails($insurance->address->postcode);

        // Get Product Details
        $product = $this->getProduct($insurance->product_id);

        // Check if Insurance Motor Record Exists
        $insurance_motor = InsuranceMotor::with(['driver'])
            ->where('insurance_id', $insurance->id)
            ->first();

        $input = (object) [
            'insurance_code' => $request->insurance_code,
            'company_id' => $product->insurance_company->id,
            'product_id' => $product->id,
            'vehicle_number' => strtoupper($request->vehicle_number ?? $insurance->motor->vehicle_number),
            'id_type' => $insurance->holder->id_type_id,
            'id_number' => $insurance->holder->id_number,
            'payment_method' => $request->payment_method,
            'payment_amount' => formatNumber($request->payment_amount),
            'payment_date' => Carbon::parse($request->payment_date)->format('Y-m-d'),
            'transaction_reference' => $request->transaction_reference,
            'insurance' => $insurance,
            'insurance_motor' => $insurance_motor,
            'region' => $postcode->state->region
        ];

        // Check Insurance Status
        if($insurance->insurance_status !== Insurance::STATUS_PAYMENT_ACCEPTED) {
            abort(config('setting.response_codes.invalid_insurance_status'), __('api.invalid_insurance_status', ['status' => getInsuranceStatus($insurance->insurance_status)]));
        }

        // Compare IC Number
        if($insurance->holder->id_number !== $input->id_number) {
            abort(config('setting.response_codes.insurance_record_mismatch'), __('api.insurance_record_not_match'));
        }

        // Compare Total Payable
        if(round(floatval($input->payment_amount), 2) !== round(floatval($insurance->amount), 2)) {
            abort(config('setting.response_codes.total_payable_not_match'), __('api.total_payable_not_match'));
        }

        $insurer_class = $this->getInsurerClass($input->product_id);
        $result = $insurer_class->submission($input);

        if(!$result->status) {
            Insurance::find($input->insurance->id)
                ->update(['insurance_status', Insurance::STATUS_POLICY_FAILURE]);

            // Insert Log into DB
            InsuranceRemark::create([
                'insurance_id' => $input->insurance->id,
                'remark' => "{$product->insurance_company->name} Policy Failure: {$result->response}"
            ]);

            abort($result->code, $result->response);
        }

        try {
            DB::beginTransaction();

            Insurance::find($input->insurance->id)
                ->update([
                    'policy_number' => $result->response->policy_number,
                    'insurance_status' => Insurance::STATUS_POLICY_ISSUED,
                    'cover_note_date' => Carbon::now()->format('Y-m-d')
                ]);
            
            InsuranceRemark::create([
                'insurance_id' => $input->insurance->id,
                'remark' => "{$product->insurance_company->name} Policy successfully created. (Policy Number: {$result->policy_number})",
            ]);

            DB::commit();

            $result->company = $product->insurance_company->name;
            $result->product_name = $product->name;

            return new SubmitCoverNoteResponse([
                json_decode(json_encode($result), true)
            ]);
        } catch (Exception $ex) {
            Log::error("[API/Policy Submission] An Error Encountered. {$ex->getMessage()}");
            abort(500, $ex->getMessage());
        }
    }

    private function getInsurerClass(int $product_id)
    {
        $insurer = Product::findOrFail($product_id)->insurance_company;

        switch($product_id) {
            case 2:{
                return new AmGeneral($insurer->id, $insurer->name);

                break;
            }
            case 3:{
                return new Allianz($insurer->id, $insurer->name);

                break;
            }
            case 6:{
                return new AIG($insurer->id, $insurer->name);

                break;
            }
            case 9: {
                return new PacificOrient($insurer->id, $insurer->name);

                break;
            }
            case 10:{
                return new Zurich($insurer->id, $insurer->name);

                break;
            }
            case 12:{
                return new ZurichTakaful($insurer->id, $insurer->name);

                break;
            }
            case 14: {
                return new Liberty($insurer->id, $insurer->name);

                break;
            }
            case 15: {
                return new BerjayaSompo($insurer->id, $insurer->name);

                break;
            }
            default: {
                throw new ModelNotFoundException(__('api.invalid_product'));
            }
        }
    }

    private function getPostcodeDetails(int $postcode) : Postcode
    {
        return Postcode::with('state')
            ->where('postcode', $postcode)
            ->first();
    }

    private function getProduct(int $product_id) : Product
    {
        return Product::with('insurance_company')
            ->where('id', $product_id)
            ->firstOrFail();
    }
}
