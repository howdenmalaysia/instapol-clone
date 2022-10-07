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
                'id_number' => $request->id_number,
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
        $this->checkMotorSessionObject($request);

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
        // return redirect()->route();
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
                'class_ode' => $motor->vehicle->extra_attribute->class_code ?? '',
                'coverage_code' => $motor->vehicle->extra_attribute->coverage_code ?? '',
                'vehicle_use_code' => $motor->vehicle->extra_attribute->vehicle_use_code ?? '',
                'make_code' => $motor->vehicle->extra_attribute->make_code ?? '',
                'make' => $motor->vehicle->make ?? '',
                'model_code' => $motor->vehicle->extra_attribute->model_code ?? '',
                'model' => $motor->vehicle->model ?? '',
                'year_make' => $motor->vehicle->manufacture_year ?? '',
                'engine_no' => $motor->vehicle->engine_number ?? $motor->vehicle->extra_attribute->engine_number ?? '',
                'chassis_no' => $motor->vehicle->chassis_number ?? $motor->vehicle->extra_attribute->chassis_number ?? '',
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
}
