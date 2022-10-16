<?php

namespace App\Http\Controllers\Insurance;

use App\DataTransferObjects\Motor\QuotationData;
use App\DataTransferObjects\Motor\VehicleData;
use App\Http\Controllers\Controller;
use App\Models\Motor\InsuranceCompany;
use App\Models\Motor\Product;
use App\Models\Motor\Quotation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

        switch ($request->id_type) {
            case 1: {
                $gender = getGenderFromIC($request->id_number);
                $driving_experience = getAgeFromIC($request->id_number) - 18;
                $marital_status = 'S';

                break;
            }
            case 2: {
                $gender = $marital_status = 'O';

                break;
            }
        }

        $session = (object) [
            'referrer' => $request->cookie('referrer'),
            'vehicle_number' => $request->vehicle_number,
            'postcode' => $request->postcode,
            'policy_holder' => (object) [
                'id_type' => $request->id_type,
                'id_number' => formatIC($request->id_number),
                'email' => $request->email,
                'phone_number' => $request->phone_number,
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
        
        $session = $request->session()->get('motor');

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
        $session->vehicle->inception_date = Carbon::createFromFormat('d M Y', $session->vehicle->inception_date)->format('Y-m-d');
        $session->vehicle->expiry_date = Carbon::createFromFormat('d M Y', $session->vehicle->expiry_date)->format('Y-m-d');
        $session->vehicle->nvic = $request->nvic;
        $session->coverage_type = $request->coverage_type;
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

        $products = Product::with('insurance_company')->get();

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
        $motor->product_id = intval($request->product_id);
        $motor->policy_holder->gender = $request->gender;
        $motor->policy_holder->marital_status = $request->marital_status;
        $motor->av_code = $request->av_code;

        if($premium->vehicle->nvic === '-') {
            $premium->vehicle->nvic = $motor->vehicle->nvic;
        }

        $motor->vehicle = $premium->vehicle;
        $motor->premium = (object) [
            'basic_premium' => $premium->basic_premium,
            'ncd' => $premium->ncd_percentage,
            'total_benefit_amount' => $premium->total_benefit_amount,
            'loading_percentage' => $premium->loading_percentage,
			'loading_amount' => $premium->loading_amount,
			'gross_premium' => $premium->gross_premium,
			'sst_percent' => $premium->sst_percent,
			'sst_amount' => $premium->sst_amount,
			'stamp_duty' => $premium->stamp_duty,
			'total_payable' => $premium->total_payable,
			'total_contribution' => $premium->total_contribution,
        ];

        // $motor->extra_cover_list = 
    }

    public function compareDetail(Request $request)
    {

    }

    public function compareDetail_POST(Request $request)
    {
        
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
                'make_code' => $motor->vehicle->extra_attribute->make_code ?? '',
                'make' => $motor->vehicle->make ?? '',
                'model_code' => $motor->vehicle->extra_attribute->model_code ?? '',
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
            'make_code' => $data->vehicle->extra_attribute->make_code ?? '',
            'make' => $data->vehicle->make ?? '',
            'model_code' => $data->vehicle->extra_attribute->model_code ?? '',
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
        $param->h_company_id = $param->h_product_id = '';

        $quotation->product_type = $data->product_type ?? 2;
        $quotation->email_address = $data->policy_holder->email;
        $quotation->request_param = json_encode($param);
        $quotation->save();

        $response['quotation'] = $quotation;
        $response['vehicle'] = json_encode($vehicle);

        return $response;
    }
}
