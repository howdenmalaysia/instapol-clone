<?php

namespace App\Http\Controllers\API;

use App\DataTransferObjects\Motor\APIData;
use App\DataTransferObjects\Motor\FullQuoteResponse;
use App\DataTransferObjects\Motor\QuoteResponse;
use App\DataTransferObjects\Motor\Response\QuotationResponse;
use App\DataTransferObjects\Motor\Response\SubmitCoverNoteResponse;
use App\DataTransferObjects\Motor\VehicleData;
use App\DataTransferObjects\Motor\VehicleVariantData;
use App\Helpers\Insurer\PacificOrient;
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
use App\Models\Motor\VehicleBodyType;
use App\Models\Postcode;
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
            'id_type' => $request->id_type,
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

            if(!$result->status) {
                return $this->abort($result->response);
            }         
    
            // Include Insurer Details in Response
            $product = Product::with(['insurance_company'])
                ->where('id', $request->product_id)
                ->first();
            $result->response->insurer = $product->insurance_company->name;
            $result->response->product_name = $product->name;
    
            return new VehicleVariantData([
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
            ]);
        } catch(Exception $ex) {
            Log::error("[API/GetVehicleDetails] An error occurred. {$ex->getMessage()}");
            
            return $this->abort($ex->getMessage());
        }

    }

    public function getQuote(Request $request, $quote_type)
    {
        $full_quote = $quote_type === 'full';

        // Get State Details with Postcode
        $postcode = $this->getPostcodeDetails($request->postcode);
        
        // Get Product Details
        $product = $this->getProduct($request->product_id);

        // Get Vehicle Body Type Details
        $vehicle_body_type_id = VehicleBodyType::where('name', $request->vehicle_body_type)->pluck('id');

        $data = new APIData([
            'id_type' => $request->id_type,
            'id_number' => $request->id_number,
            'vehicle_number' => strtoupper($request->vehicle_number),
            'postcode' => $postcode->postcode,
            'email' => $request->email,
            'region' => $postcode->state->region,
            'state' => $postcode->state->name,
            'insurer_id' => $product->insurance_company->id,
            'insurer_name' => $product->insurance_company->name,
            'product_id' => $product->id,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'nvic' => $request->nvic,
            'vehicle' => new VehicleData($request->vehicle),
            'extra_cover' => toObject($request->extra_cover ?? []),
            'additional_driver' => toObject($request->additional_driver ?? []),
            'vehicle_body_type' => $vehicle_body_type_id,
            'phone_number' => $request->phone_number,
            'occupation' => strtoupper($request->occupation)
        ]);

        $insurer_helper = $this->getInsurerClass($product->insurance_company->id);
        $result = $insurer_helper->premiumDetails($data, $full_quote);

        if(!$result->status) {
            return abort($result->code, $result->response);
        }

        // Include Company & Product Details
        $result->response->company = $product->insurance_company->name;
        $result->response->product_name = $product->name;

        if($full_quote) {
            $quote = new FullQuoteResponse($result->response->attributesToArray());
        } else {
            $quote = new QuoteResponse($result->response->attributesToArray());
        }

        return $quote->all();
    }

    public function createQuotation(Request $request) : QuotationResponse
    {
        // Get State Details with Postcode
        $postcode = $this->getPostcodeDetails($request->postcode);

        // Get Product Details
        $product = $this->getProduct($request->product_id);

        // Get Vehicle Body Type Details
        if(!empty($request->vehicle_body_type)) {
            $vehicle_body_type_id = VehicleBodyType::where('name', $request->vehicle_body_type)
                ->get()
                ->id;
        }

        $input = new APIData([
            'id_type' => $request->id_type,
            'id_number' => $request->id_number,
            'nationality' => 'MYS',
            'vehicle_number' => strtoupper($request->vehicle_number),
            'postcode' => $request->postcode,
            'email' => $request->email,
            'region' => $postcode->state->region,
            'state' => strtoupper($postcode->state->name),
            'company_id' => $product->insurance_company->id,
            'product_id' => $product->id,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'vehicle' => toObject($request->vehicle),
            'extra_cover' => toObject($request->extra_cover ?? []),
            'additional_driver' => toObject($request->additional_driver ?? []),
            'vehicle_body_type' => $vehicle_body_type_id,
            'name' => strtoupper($request->name),
            'date_of_birth' => $request->date_of_birth,
            'age' => $request->id_type === 1 ? getAgeFromIC($request->id_number) : null,
            'phone_code' => $request->phone_code,
            'phone_number' => $request->phone_number,
            'unit_no' => $request->unit_no,
            'building_name' => strtoupper($request->building_name),
            'address_one' => strtoupper($request->addreaa_one),
            'address_two' => strtoupper($request->address_two),
            'city' => strtoupper($request->city),
            'referral_code' => $request->referral_code,
            'occupation' => strtoupper($request->occupation),
        ]);

        // Remove '0' from phone number if exists
        if(substr($input->phone_number, 0, 1) == '0') {
            $input->phone_number = substr($input->phone_number, 1);
        }

        $insurer_class = $this->getInsurerClass($product->insurance_company->id);
        $result = $insurer_class->premiumDetails($input);

        if(!$result->status) {
            return $this->abort($result->response, $result->code);
        }

        $quotation = $result->response;
        $insurance_code = '';

        // Check if the user exists in the system
        $user = User::where('email', $request->email)->get();

        if(empty($user)) {
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
            $insurance_motor = InsuranceMotor::where('vehicle_number', $input->vehicle_number)->get();

            // 2a. Update or Insert to Insurance table
            $insurance = Insurance::updateOrCreate([
                'id' => $insurance_motor->insurance_id ?? null
            ], [
                'product_id' => $product->id,
                'customer_id' => $user->id,
                'insurance_status' => Insurance::STATUS_NEW_QUOTATION,
                'referrer' => $request->referrer,
                'inception_date' => Carbon::parse($result->inception_date) ->format('Y-m-d'),
                'expiry_date' => Carbon::parse($result->expiry_date) ->format('Y-m-d'),
                'amount' => $quotation->total_payable,
                'quotation_date' => Carbon::now()->format('Y-m-d'),
                'channel' => 'online',
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
                'name' => $input->name,
                'id_type_id' => $input->id_type,
                'id_number' => $input->id_number,
                'nationality' => $input->nationality,
                'date_of_birth' => $input->date_of_birth,
                'age' => $input->age,
                'gender' => $input->gender,
                'phone_code' => $input->phone_code,
                'phone_number' => $input->phone_number,
                'email_address' => $input->email,
                'occupation' => $input->occupation,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 4. Update or Insert to Insurance Address Table
            InsuranceAddress::updateOrCreate([
                'insurance_id' => $insurance->id
            ], [
                'unit_no' => $input->unit_no,
                'building_name' => $input->building_name,
                'address_one' => $input->address_one,
                'address_two' => $input->address_two,
                'city' => $input->city,
                'state' => $input->state,
                'postcode' => $input->postcode,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 5. Update or Insert to Insurance Motor Table
            InsuranceMotor::updateOrCreate([
                'insurance_id' => $insurance->id
            ], [
                'vehicle_state_id' => $postcode->state->id,
                'vehicle_number' => $quotation->vehicle_number,
                'chassis_number' => $quotation->vehicle->chassis_number,
                'engine_number' => $quotation->vehicle->engine_number,
                'make' => $quotation->vehicle->make,
                'model' => $quotation->vehicle->model,
                'seating_capacity' => $quotation->vehicle->extra_attributes->seating_capacity,
                'engine_capacity' => $quotation->vehicle->engine_capacity,
                'manufacture_year' => $quotation->vehicle->manufacture_year,
                'market_value' => $quotation->vehicle->sum_insured,
                'nvic' => $quotation->vehicle->nvic,
                'variant' => $quotation->vehicle->variant,
                'ncd_percentage' => $quotation->vehicle->ncd_percentage,
                'ncd_amount' => $quotation->ncd_amount,
                'previous_ncd_percentage' => $this->getNCDPercentage($quotation->vehicle->ncd_percentage)->previous,
                'next_ncd_percentage' => $this->getNCDPercentage($quotation->vehicle->ncd_percentage)->next,
                'previous_inception_date' => Carbon::parse($input->expiry_date)->subYear()->addDay(),
                'previous_expiry_date' => Carbon::parse($input->expiry_date)->subYear()->addDays(2),
                'previous_policy_expiry' => Carbon::parse($input->expiry_date)->subYear()->addDay(2),
                'disabled' => 'N',
                'marital_status' => $input->marital_status,
                'driving_experience' => $input->age - 18 > 0 ? $input->age - 18 : 1,
                'loading' => 0.00,
                'number_of_drivers' => $input->additional_driver->count(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 6a. Delete Existing Record
            InsuranceExtraCover::where('insurance_id', $insurance->id)->delete();
            // 6b. Update or Insert to Insurance Extra Cover Table
            foreach($input->extra_cover as $extra_cover) {
                InsuranceExtraCover::create([
                    'insurance_id' => $insurance->id,
                    'insurance_extra_cover_type_id',
                    'code' => $extra_cover->code,
                    'description' => $extra_cover->description,
                    'sum_insured' => $extra_cover->sum_insured,
                    'amount' => $extra_cover->total_payable,
                ]);
            }

            // 7. Update or Insert to Insurance Motor PA Table
            // 8. Update or Insert to Insurance Motor Roadtax Table
            InsuranceMotorRoadtax::updateOrCreate([
                'insurance_id' => $insurance->id
            ], [
                'roadtax_delivery_region_id',
                'roadtax_renewal_fee',
                'e_service_fee',
                'tax',
                'issued',
                'tracking_code',
                'admin_charge',
                'success',
                'active',
            ]);

            // 9a. Delete Existing Records
            InsuranceMotorDriver::where('insurance_id', $insurance->id)->delete();
            // 9. Update or Insert to Insurance Motor Driver Table
            foreach($input->additional_driver as $additional_driver) {
                InsuranceMotorDriver::create([
                    'insurance_id' => $insurance->id,
                    'name' => $additional_driver->name,
                    'id_number' => $additional_driver->id_number,
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
                'remarks'
            ]);

            // 11. Insert to Insurance Remarks Table
            InsuranceRemark::create([
                'insurance_id' => $insurance->id,
                'remark' => "{$product->insurance_company->name} Quotation generated successfully. (Insurance Code: {$insurance_code})"
            ]);

            // 12. Delete Insurance Promo Table
            InsurancePromo::where([
                'insurance_id' => $insurance->id
            ])->delete();

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
                'insurance_id' => $input->insurance->id
            ], [
                'value' => json_encode($input->vehicle->extra_attribute)
            ]);

            DB::commit();
            Log::info("[API/CreateQuotation] Executed Sucessfully.");

            $quotation->insurance_company = $product->insurance_company->name;
            $quotation->product_name = $product->name;

            return new QuotationResponse([
                'insurance_code' => $insurance_code,
                'quotation' => json_decode(json_encode($quotation), true),
            ]);
        } catch (Exception $ex) {
            Log::error("[API/CreateQuotation] An Error Encountered. {$ex->getMessage()}");
            DB::rollBack();
        }
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
                'driver',
                'roadtax',
                'promo',
                'remark'
            ])
            ->where('insurance_code', $request->insurance_code)
            ->get();

        // Get State Details with Postcode
        $postcode = $this->getPostcodeDetails($request->postcode);

        // Get Product Details
        $product = $this->getProduct($request->product_id);

        // Check if Insurance Motor Record Exists
        $insurance_motor = InsuranceMotor::where('insurance_id', $insurance->id);

        $input = (object) [
            'insurance_code' => $request->insurance_code,
            'company_id' => $product->insurance_company->id,
            'product_id' => $product->id,
            'vehicle_number' => strtoupper($request->vehicle_number),
            'id_type' => $insurance->holder->id_type_id,
            'id_number' => $request->id_number,
            'payment_method' => $request->payment_method,
            'payment_amount' => formatNumber($request->payment_amount),
            'payment_date' => Carbon::parse($request->payment_date)->format('Y-m-d'),
            'transaction_reference' => $request->transaction_reference,
            'insurance' => $insurance,
            'insurance_motor' => $insurance_motor,
            'region' => $postcode->state->region
        ];

        // Check Insurance Status
        if($insurance->insurance_status !== Insurance::STATUS_NEW_QUOTATION) {
            abort(config('setting.response_codes.invalid_insurance_status'), __('api.invalid_insurance_status', ['status' => getInsuranceStatus($insurance->insurance_status)]));
        }

        // Compare IC Number
        if($insurance->holder->id_number !== $input->id_number) {
            abort(config('setting.response_codes.insurance_record_mismatch'), __('api.insurance_record_not_match'));
        }

        // Compare Total Payable
        if(round(floatval($input->payment_amount), 2) !== round(floatval($insurance->total_payable), 2)) {
            abort(config('setting.response_codes.total_payable_not_match'), __('api.total_payable_not_match'));
        }

        $insurer_class = $this->getInsurerClass($input->company_id);
        $result = $insurer_class->submission($input);

        if(!$result->status) {
            Insurance::find($input->insurance->id)
                ->update(['insurance_status', Insurance::STATUS_POLICY_FAILURE]);

            // Insert Log into DB
            InsuranceRemark::create([
                'insurance_id' => $input->insurance->id,
                'remark' => "{$product->insurance_company->name} Policy Failure: {json_encode($result->response)}"
            ]);

            abort($result->code, $result->response);
        }

        try {
            DB::beginTransaction();

            Insurance::find($input->insurance->id)
                ->update([
                    'policy_number' => $result->policy_number,
                    'insurance_status' => Insurance::STATUS_POLICY_ISSUED
                ]);
            
            InsuranceRemark::create([
                'insurance_id' => $input->insurance->id,
                'remark' => "{$product->insurance_company->name} Policy successfully created. (Policy Number: {$policy_policy_number})",
            ]);

            DB::commit();

            $result->company = $product->insurance_company->name;
            $result->product_name = $product->name;

            return new submitCoverNoteResponse([
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
            case 10: {
                return new PacificOrient($insurer->id, $insurer->name);

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

    private function getNCDPercentage($current_ncd) : object
    {
        $ncd = [0, 25, 30, 38.33, 45, 55];

        $previous_ncd = $ncd[array_search($current_ncd, $ncd) - 1];
        $next_ncd = $ncd[array_search($current_ncd, $ncd) + 1];

        return (object) [
            'current' => $current_ncd,
            'previous' => $previous_ncd,
            'next' => $next_ncd
        ];
    }
}
