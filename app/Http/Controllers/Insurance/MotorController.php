<?php

namespace App\Http\Controllers\Insurance;

use App\DataTransferObjects\Motor\QuotationData;
use App\DataTransferObjects\Motor\VehicleData;
use App\Http\Controllers\Controller;
use App\Mail\PaymentReceipt;
use App\Models\InsurancePremium;
use App\Models\Motor\Insurance;
use App\Models\Motor\InsuranceAddress;
use App\Models\Motor\InsuranceCompany;
use App\Models\Motor\InsuranceExtraCover;
use App\Models\Motor\InsuranceHolder;
use App\Models\Motor\InsuranceMotor;
use App\Models\Motor\Product;
use App\Models\Motor\Quotation;
use App\Models\Motor\RoadtaxDeliveryType;
use App\Models\Postcode;
use App\Models\Relationship;
use App\Models\State;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class MotorController extends Controller
{
    public function index(Request $request)
    {
        $insurers = InsuranceCompany::orderBy('sequence')
            ->get();
        
        $motor = $request->session()->get('motor', []);        
        return view('frontend.motor.index')->with(['insurers' => $insurers, 'motor' => $motor]);
    }

    public function index_POST(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type' => ['required', 'numeric', Rule::in([1, 2])],
            'vehicle_number' => 'required|string',
            'postcode' => 'required|numeric',
            'id_number' => 'required|string',
            'phone_number' => 'required|numeric',
            'email' => 'required|email:rfc,dns'
        ]);

        if ($validator->fails()) {
            return redirect()->route('motor.index')->withErrors($validator->errors());
        }

        // Extract User Data
        $gender = $marital_status = '';
        $driving_experience = 0;
        $id_type = null;

        switch ($request->id_type) {
            case 1: {
                $gender = getGenderFromIC($request->id_number);
                $driving_experience = getAgeFromIC($request->id_number) - 18;
                $marital_status = 'S';
                $id_type = config('setting.id_type.nric_no');

                break;
            }
            case 2: {
                $gender = $marital_status = 'O';
                $id_type = config('setting.id_type.company_registration_no');

                break;
            }
        }

        $session = (object) [
            'referrer' => $request->cookie('referrer'),
            'vehicle_number' => $request->vehicle_number,
            'postcode' => $request->postcode,
            'policy_holder' => (object) [
                'id_type' => $id_type,
                'id_number' => formatIC($request->id_number),
                'email' => $request->email,
                'phone_number' => Str::startsWith($request->phone_number, '0') ? substr($request->phone_number, 1) : $request->phone_number,
                'date_of_birth' => formatDateFromIC($request->id_number),
                'gender' => $gender,
                'marital_status' => $marital_status,
                'driving_experience' => $driving_experience,
            ]
        ];

        $quotation = $this->quotation($session);
        $session->quotation_id = $quotation->id;
        $request->session()->put('motor', $session);

        return redirect()->route('motor.vehicle-details');
    }

    public function vehicleDetails(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }

        $products = Product::with('insurance_company')->get();
        $product_ids = $insurer_ids = [];
        foreach($products as $product) {
            array_push($product_ids, $product->id);
            array_push($insurer_ids, $product->insurance_company->id);
        }

        return view('frontend.motor.vehicle_details')->with([
            'product_ids' => $product_ids,
            'insurer_ids' => $insurer_ids
        ]);
    }

    public function vehicleDetails_POST(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }
        
        $session = json_decode($request->motor);

        // Reformat Dates
        $session->vehicle->inception_date = Carbon::parse($session->vehicle->inception_date)->format('Y-m-d');
        $session->vehicle->expiry_date = Carbon::parse($session->vehicle->expiry_date)->format('Y-m-d');
        $session->vehicle->nvic = $request->nvic;
        $session->vehicle->variant = $request->variant;
        $session->vehicle->coverage_type = $request->coverage_type;
        $session->user_id = auth()->user()->id ?? '';

        if(!empty($session->quotation_id)) {
            $quote = $this->updateQuotation($session);
        } else {
            $quote = $this->quotation($session);
        }

        $session->quotation_id = $quote['quotation']->id ?? $session->quotation_id ?? '';
        $request->session()->put('motor', $session);
        
        return redirect()->route('motor.compare');
    }

    public function compare(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }
        
        $session = $request->session()->get('motor');

        if($session->policy_holder->id_type === config('setting.id_type.company_registration_no')) {
            if(empty($session->policy_holder->gender)) {
                $session->policy_holder->gender = 'O';
            }

            if(empty($session->policy_holder->marital_status)) {
                $session->policy_holder->marital_status = 'O';
            }
        }

        $products = Product::with(['insurance_company', 'benefits'])->get();

        return view('frontend.motor.compare')->with(['products' => $products]);
    }

    public function compare_POST(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }
        
        $motor = json_decode($request->motor);
        $premium = json_decode($request->premium);

        // Update Session
        $motor->user_id = auth()->user()->id ?? '';
        $motor->policy_holder->gender = $request->gender;
        $motor->policy_holder->marital_status = $request->marital_status;
        $motor->av_code = $request->av_code;

        $motor->premium = (object) [
            'basic_premium' => $premium->basic_premium,
            'ncd_amount' => $premium->ncd_amount,
            'total_benefit_amount' => $premium->total_benefit_amount,
			'loading' => $premium->loading,
			'gross_premium' => $premium->gross_premium,
			'sst_percent' => $premium->sst_percent,
			'sst_amount' => $premium->sst_amount,
			'stamp_duty' => $premium->stamp_duty,
			'total_payable' => $premium->total_payable,
        ];

        $motor->vehicle->sum_insured = $premium->sum_insured;
        $motor->vehicle->sum_insured_type = $premium->sum_insured_type;
        $motor->vehicle->min_sum_insured = $premium->min_sum_insured;
        $motor->vehicle->max_sum_insured = $premium->max_sum_insured;

        $motor->extra_cover_list = $premium->extra_cover;
        $motor->named_drivers_needed = $premium->named_drivers_needed;

        $request->session()->put('motor', $motor);

        return redirect()->route('motor.add-ons');
    }

    public function addOns(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }

        $session = $request->session()->get('motor');

        $relationships = Relationship::all();

        $product = Product::find($session->product_id);

        return view('frontend.motor.add_ons')->with([
            'relationships' => $relationships,
            'premium' => $session->premium,
            'product' => $product
        ]);
    }

    public function addOns_POST(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }

        $session = $request->session()->get('motor');

        if(!empty($request->selected_extra_coverage)) {
            $selected_extra_cover = [];
            
            foreach(json_decode($request->selected_extra_coverage) as $selected) {
                foreach($session->extra_cover_list as $extra_cover) {
                    if($extra_cover->extra_cover_code === $selected->extra_cover_code) {
                        array_push($selected_extra_cover, (object) [
                            'sum_insured' => $selected->sum_insured ?? $extra_cover->sum_insured,
                            'description' => $extra_cover->extra_cover_description,
                            'code' => $selected->extra_cover_code
                        ]);
                    }
                }
            }

            $session->selected_extra_coverage = $selected_extra_cover;
        }

        if(!empty($request->additional_driver)) {
            $session->additional_drivers = $request->additional_drivers;
        }

        if(!empty($request->roadtax)) {
            $session->roadtax = json_decode($request->roadtax);
            $session->premium->roadtax = $session->roadtax->total;
            $session->premium->total_payable += $session->roadtax->total;
        }

        $request->session()->put('motor', $session);

        return redirect()->route('motor.policy-holder');
    }

    public function policyHolder(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }

        $session = $request->session()->get('motor');
        $product = Product::find($session->product_id);
        $states = State::all('name');
        $city = Postcode::with(['state'])->where('postcode', $session->postcode)->first();

        return view('frontend.motor.policy_holder')->with([
            'premium' => $session->premium,
            'product' => $product,
            'states' => $states,
            'city' => $city,
        ]);
    }

    public function policyHolder_POST(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }

        $motor = json_decode($request->motor);

        $motor->insurance_code = $request->insurance_code;
        $motor->quotation = json_decode($request->quotation);

        $data = (object) [
            'quotation_id' => $motor->quotation_id ?? $motor->quotation->id,
            'insurance_code' => $request->insurance_code,
            'vehicle_number' => $motor->vehicle_number,
            'vehicle' => $motor->vehicle,
            'variants' => $motor->variants,
            'product_type_id' => Product::TYPE_MOTOR,
            'postcode' => $motor->postcode,
            'policy_holder' => (object) [
                'id_type' => $motor->policy_holder->id_type,
                'id_number' => formatIC($motor->policy_holder->id_number),
                'email' => $request->email ?? $motor->policy_holder->email,
                'phone_number' => '0' . ($request->phone_number ?? $motor->policy_holder->phone_number),
                'date_of_birth' => formatDateFromIC($motor->policy_holder->id_number),
                'gender' => $motor->policy_holder->gender,
                'marital_status' => $motor->policy_holder->marital_status,
                'driving_experience' => $motor->policy_holder->driving_experience,
                'name' => $request->name,
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'city' => $request->city,
                'state' => $request->state
            ],
        ];

        $this->updateQuotation($data);

        $request->session()->put('motor', $motor);

        return redirect()->route('motor.payment-summary');
    }

    public function paymentSummary(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }

        $session = $request->session()->get('motor');

        $insurance = Insurance::findByInsuranceCode($session->insurance_code);

        if(empty($insurance)) {
            return $this->abort(__('api.insurance_record_not_match'));
        }

        if($insurance->insurance_status === Insurance::STATUS_NEW_QUOTATION || $insurance->insurance_status === Insurance::STATUS_PAYMENT_FAILURE) {
            // Get Policy Holder Details
            $policy_holder = InsuranceHolder::with(['id_type'])
                ->where('insurance_id', $insurance->id)
                ->first();

            // Get Policy Holder Address
            $address = InsuranceAddress::where('insurance_id', $insurance->id)
                ->first();

            $strings = [
                $address->unit_no,
                $address->building_name,
                $address->address_one,
                $address->address_two,
                $address->city,
                $address->postcode,
                $address->state
            ];

            $policy_holder->address = implode(', ', formatAddress($strings));

            // Get Vehicle Details
            $motor = InsuranceMotor::with(['driver', 'roadtax'])
                ->where('insurance_id', $insurance->id)
                ->first();

            // Get Selected Extra Covers Details
            $extra_cover = InsuranceExtraCover::where('insurance_id', $insurance->id)
                ->first();

            // Get Product Details
            $product = Product::with(['insurance_company', 'product_type'])
                ->where('id', $insurance->product_id)
                ->first();

            // Get Roadtax Delivery Fee
            if(!empty($motor->roadtax)) {
                $motor->roadtax->delivery_fee = RoadtaxDeliveryType::find($motor->roadtax->roadtax_delivery_region_id)->amount;
            }
        } else if($insurance->insurance_status === Insurance::STATUS_PAYMENT_ACCEPTED || $insurance->insurance_status === Insurance::STATUS_POLICY_ISSUED) {
            return redirect()->route('motor.payment-success');
        } else {
            return redirect()->route('motor.index');
        }

        return view('frontend.motor.summary')->with([
            'insurance' => $insurance,
            'policy_holder' => $policy_holder,
            'motor' => $motor,
            'extra_cover' => $extra_cover,
            'product' => $product
        ]);
    }

    public function paymentSuccess(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }
        
        $insurance_code = $request->session()->get('motor');

        $insurance = Insurance::findByInsuranceCode($insurance_code);

        // Send Success Email
        $data = (object) [
            'insurance_code' => $insurance->insurance_code,
            'insured_name' => $insurance->holder->name,
            'product_name' => $insurance->product->name,
            'total_premium' => number_format($insurance->total_payable, 2),
            'total_payable' => number_format($insurance->total_payable, 2)
        ];

        $user = User::where('email', $insurance->holder->email)->first();
        if(empty($user)) {
            User::create([
                'email' => $insurance->holder->email,
                'name' => $insurance->holder->name,
                'password' => Hash::make(Str::random(8)),
            ]);
        }

        Mail::to($insurance->holder->email)
            ->cc([config('setting.howden.affinity_team_email'), config('email_cc_list')])
            ->send(new PaymentReceipt($data));

        return view('frontend.motor.payment_success')
            ->with([
                'insurance' => $insurance,
                'total_payable' => number_format($insurance->amount, 2)
            ]);
    }

    private function quotation(object $motor)
    {
        $inception_date = null;
        if(!empty($motor->vehicle->inception_date)) {
            $inception_date = Carbon::createFromFormat('Y-m-d', $motor->vehicle->inception_date);
        }

        if(!empty($motor->vehicle->ncd_percentage)) {
            $vehicle = new VehicleData([
                'vehicle_number' => $motor->vehicle_number ?? '',
                'class_code' => $motor->vehicle->extra_attribute->class_code ?? '',
                'coverage_code' => $motor->vehicle->extra_attribute->coverage_code ?? '',
                'vehicle_use_code' => $motor->vehicle->extra_attribute->vehicle_use_code ?? '',
                'make_code' => $motor->vehicle->extra_attribute->make_code ?? 0,
                'make' => $motor->vehicle->make ?? '',
                'model_code' => $motor->vehicle->extra_attribute->model_code ?? 0,
                'model' => $motor->vehicle->model ?? '',
                'manufacture_year' => $motor->vehicle->manufacture_year ?? '',
                'engine_number' => $motor->vehicle->engine_number ?? $motor->vehicle->extra_attribute->engine_number ?? '',
                'chassis_number' => $motor->vehicle->chassis_number ?? $motor->vehicle->extra_attribute->chassis_number ?? '',
                'nvic' => $motor->vehicle->nvic ?? $motor->variants[0]->nvic ?? '',
                'variant' => $motor->vehicle->variant ?? $motor->variants[0]->variant ?? '',
                'seating_capacity' => $motor->vehicle->extra_attribute->seating_capacity ?? '',
                'engine_capacity' => $motor->vehicle->engine_capacity ?? '',
                'ncd_effective_date' => !empty($inception_date) ? $inception_date->subYear()->format('Y-m-d') : '',
                'ncd_expiry_date' => !empty($inception_date) ? $inception_date->subDay()->format('Y-m-d') : '',
                'current_ncd' => $motor->vehicle->ncd_percentage ?? '',
                'next_ncd' => $motor->vehicle->ncd_percentage ?? '',
                'next_ncd_effective_date' => $motor->vehicle->inception_date ?? '',
                'policy_expiry_date' => !empty($inception_date) ? $inception_date->subDay()->format('Y-m-d') : '',
            ]);
        }

        $quote = new QuotationData([
            'vehicle_postcode' => $motor->postcode ?? '',
            'vehicle_no' => $motor->vehicle_number ?? '',
            'id_type' => $motor->policy_holder->id_type ?? '',
            'id_no' => formatIC($motor->policy_holder->id_number ?? ''),
            'email_address' => $motor->policy_holder->email ?? '',
            'name' => $motor->policy_holder->name ?? '',
            'phone_number' => $motor->policy_holder->phone_number ?? '',
            'postcode' => $motor->postcode ?? '',
            'h_vehicle' => $vehicle ?? null,
            'h_vehicle_list' => $vehicle ?? null,
        ]);
        
        $response = Quotation::updateOrCreate([
            'product_type' => $quote->product_type,
            'vehicle_number' => $quote->vehicle_no,
            'email_address' => $quote->email_address,
            'active' => Quotation::ACTIVE,
        ], [
            'request_param' => json_encode($quote),
            'referrer' => $motor->referrer,
            'compare_page' => 0
        ]);

        return $response;
    }

    private function updateQuotation(object $data)
    {
        $quotation = Quotation::findOrFail($data->quotation_id);
        $param = json_decode($quotation->request_param);

        $vehicle = new VehicleData([
            'vehicle_number' => $data->vehicle_number ?? '',
            'class_code' => $data->vehicle->extra_attribute->class_code ?? '',
            'coverage_code' => $data->vehicle->extra_attribute->coverage_code ?? '',
            'vehicle_use_code' => $data->vehicle->extra_attribute->vehicle_use_code ?? '',
            'make_code' => $data->vehicle->extra_attribute->make_code ?? 0,
            'make' => $data->vehicle->make ?? '',
            'model_code' => $data->vehicle->extra_attribute->model_code ?? 0,
            'model' => $data->vehicle->model ?? '',
            'manufacture_year' => $data->vehicle->manufacture_year ?? '',
            'engine_number' => $data->vehicle->extra_attribute->engine_number,
            'chassis_number' => $data->vehicle->extra_attribute->chassis_number,
            'market_value' => $data->vehicle->sum_insured ?? 0.00,
            'purchase_price' => 0.00,
            'style' => '',
            'nvic' => $data->vehicle->nvic,
            'variant' => $data->variants[0]->variant ?? '',
            'seating_capacity' => $data->vehicle->extra_attribute->seating_capacity ?? '',
            'engine_capacity' => $data->vehicle->engine_capacity ?? '',
            'ncd_effective_date' => Carbon::createFromFormat('Y-m-d', $data->vehicle->inception_date)->subYear()->format('Y-m-d'),
            'ncd_expiry_date' => Carbon::createFromFormat('Y-m-d', $data->vehicle->inception_date)->subDay()->format('Y-m-d'),
            'current_ncd' => $data->vehicle->ncd_percentage ?? '',
            'next_ncd' => $data->vehicle->ncd_percentage ?? '',
            'next_ncd_effective_date' => $data->vehicle->inception_date ?? '',
            'policy_expiry_date' => Carbon::createFromFormat('Y-m-d', $data->vehicle->inception_date)->subDay()->format('Y-m-d'),
            'assembly_type_code' => '',
            'min_market_value' => $data->vehicle->min_sum_insured ?? 0.00,
            'max_market_value' => $data->vehicle->max_sum_insured ?? 0.00,
            'ncd_code' => '',
        ]);

        $param->h_vehicle = json_encode($vehicle);
        $param->product_type = $data->product_type_id ?? 2;
        $param->vehicle_postcode = $data->postcode ?? '';
        $param->vehicle_number = $data->vehicle_number ?? '';
        $param->id_type = $data->policy_holder->id_type;
        $param->id_number = str_replace('-', '', $data->policy_holder->id_number);
        $param->email_address = $data->policy_holder->email ?? '';
        $param->name = $data->policy_holder->name ?? '';
        $param->phone_number = $data->policy_holder->phone_number ?? '';

        $quotation->product_type = $data->product_type ?? 2;
        $quotation->email_address = $data->policy_holder->email;
        $quotation->request_param = json_encode($param);
        $quotation->save();

        $response['quotation'] = $quotation;
        $response['vehicle'] = json_encode($vehicle);

        return $response;
    }
}
