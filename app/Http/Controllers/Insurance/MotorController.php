<?php

namespace App\Http\Controllers\Insurance;

use App\DataTransferObjects\Motor\QuotationData;
use App\DataTransferObjects\Motor\VehicleData;
use App\Http\Controllers\Controller;
use App\Models\Motor\Quotation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MotorController extends Controller
{
    public function index()
    {
        return view('frontend.motor.index');
    }

    public function index_POST(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type' => ['required', 'numeric', Rule::in([1, 2])],
            'vehicle_number' => 'required|string',
            'postcode' => 'required|numeric',
            'ic_number' => 'required|string',
            'phone_number' => 'required|numeric',
            'email' => 'required|email:rfc,dns'
        ]);

        if ($validator->fails()) {
            return view('frontend.motor.index')->withErrors($validator->errors());
        }

        // Extract User Data
        $gender = $marital_status = '';
        $driving_experience = 0;

        switch ($request->input('id_type')) {
            case 1: {
                    $gender = getGenderFromIC($request->input('ic_number'));
                    $driving_experience = getAgeFromIC($request->input('ic_number')) - 18;

                    break;
                }
            case 2: {
                    $gender = $marital_status = 'O';

                    break;
                }
        }

        $session = (object) [
            'referrer' => $request->cookie('referrer'),
            'vehicle_number' => $request->input('vehicle_number'),
            'postcode' => $request->input('postcode'),
            'policy_holder' => [
                'id_type' => $request->input('id_type'),
                'ic_number' => $request->input('ic_number'),
                'email' => $request->input('email'),
                'phone_number' => $request->input('phone_number'),
                'date_of_birth' => formatDateFromIC($request->input('ic_number')),
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

        $session = $request->session()->get('motor');
    }

    private function quotation(object $motor)
    {
        $inception_date = null;
        if(!empty($motor->vehicle->inception_date)) {
            $inception_date = Carbon::createFromFormat('Y-m-d', $motor->vehicle->inception_date);
        }

        $vehicle = new VehicleData([
            'vehRegNo' => $motor->vehicle_number ?? '',
            'classCode' => $motor->vehicle->extra_attribute->class_code ?? '',
            'coverage_code' => $motor->vehicle->extra_attribute->coverage_code ?? '',
            'vehicle_use_code' => $motor->vehicle->extra_attribute->vehicle_use_code ?? '',
            'makeCode' => $motor->vehicle->extra_attribute->make_code ?? '',
            'make' => $motor->vehicle->make ?? '',
            'modelCode' => $motor->vehicle->extra_attribute->model_code ?? '',
            'model' => $motor->vehicle->model ?? '',
            'yearMake' => $motor->vehicle->manufacture_year ?? '',
            'engineNo' => $motor->vehicle->engine_number ?? $motor->vehicle->extra_attribute->engine_number ?? '',
            'chassisNo' => $motor->vehicle->chassis_number ?? $motor->vehicle->extra_attribute->chassis_number ?? '',
            'marketValue' => 0.00,
            'purchasePrice' => 0.00,
            'style' => '',
            'nvic' => $motor->vehicle->nvic ?? $motor->variants[0]->nvic ?? '',
            'variant' => $motor->vehicle->variant ?? $motor->variants[0]->variant ?? '',
            'seatingCapacity' => $motor->vehicle->extra_attribute->seating_capacity ?? '',
            'engineCapacity' => $motor->vehicle->engine_capacity ?? '',
            'ncdEffDate' => !empty($inception_date) ? $inception_date->subYear()->format('Y-m-d') : '',
            'ncdExpDate' => !empty($inception_date) ? $inception_date->subDay()->format('Y-m-d') : '',
            'curNCD' => $motor->vehicle->ncd_percentage ?? '',
            'nextNCD' => $motor->vehicle->ncd_percentage ?? '',
            'nextNcdEffDate' => $motor->vehicle->inception_date ?? '',
            'polExpDate' => !empty($inception_date) ? $inception_date->subDay()->format('Y-m-d') : '',
            'assembly_type_code' => '',
            'min_market_value' => 0.00,
            'max_market_value' => 0.00,
            'ncdcode' => '',
        ]);

        $quote = new QuotationData([
            'vehicle_postcode' => $motor->postcode ?? '',
            'vehicle_no' => $motor->vehicle_number ?? '',
            'id_type' => $motor->policy_holder->id_type ?? '',
            'id_no' => formatIC($motor->policy_holder->ic_number ?? ''),
            'email_address' => $motor->policy_holder->email ?? '',
            'name' => $motor->policy_holder->name ?? '',
            'phone_number' => $motor->policy_holder->phone_number ?? '',
            'postcode' => $motor->postcode ?? '',
            'h_vehicle' => json_encode($vehicle),
            'h_vehicle_list' => json_encode($vehicle),
        ]);
        
        $response = Quotation::updateOrCreate([
            'id' => $motor->quotation_id ?? null
        ], [
            'product_type' => $quote->product_type,
            'email_address' => $quote->email_address,
            'request_param' => json_encode($quote),
            'referrer' => $motor->referrer,
            'remarks' => '',
            'active' => 1,
            'compare_page' => 0
        ]);

        return $response;
    }
}
