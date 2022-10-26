<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\MotorAPIController;
use App\Models\EGHLLog;
use App\Models\InsuranceRemark;
use App\Models\Motor\Insurance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        Log::info("Received Payment Request: " . json_encode($request->input()));

        $insurance = Insurance::findByInsuranceCode($request->insurance_code);

        if(empty($insurance) || $insurance->isEmpty()) {
            return __('api.insurance_record_not_match');
        }

        if(round($insurance->amount, 2) !== round($request->total_payable)) {
            return back()->with(['flash_danger' => __('api.total_payable_not_match') ]);
        }

        $payment_id = generatePaymentID(count($insurance->payment), $insurance->insurance_code);
        $payment_description = $insurance->product->insurance_company->name . ' - ' . $insurance->product->name;

        if(!empty($request->description)) {
            $payment_description .= ', ' . $request->description;
        }

        $data = [
            'transaction_type' => 'SALE',
            'payment_method' => 'ANY',
            'payment_id' => $payment_id,
            'order_number' => 'Inv_' . $payment_id,
            'payment_description' => $payment_description,
            'return_url' => route('payment.callback'),
            'callback_url' => route('payment.callback'),
            'amount' => number_format($insurance->amount, 2, '.', ''),
            'currency' => 'MYR',
            'ip' => route('/'),
            'customer_name' => $insurance->policy_holder->name,
            'customer_email' => $insurance->policy_holder->email,
            'customer_phone_number' => $insurance->policy_holder->phone_number,
            'language' => 'en',
            'timeout' => 780,
        ];

        $hash = [
            config('setting.payment.eghl.merchant_password'),
            config('setting.payment.eghl.merchant_id'),
            $data['payment_id'],
            $data['return_url'],
            $data['callback_url'],
            $data['amount'],
            $data['currency'],
            $data['ip'],
            $data['timeout'],
        ];

        $data['hash'] = hash('sha256', implode('', $hash));

        EGHLLog::create([
            'transaction_type' => $data['transaction_type'],
            'payment_method' => $data['payment_method'],
            'service_id' => config('setting.payment.gateway.merchant_id'),
            'payment_id' => $payment_id,
            'order_number' => 'Inv_' . $payment_id,
            'payment_description' => $payment_description,
            'amount' => $data['amount'],
            'currency_code' => $data['currency'],
            'hash' => $hash,
        ]);

        Log::info("Request Logged Successfully.");

        return view('frontend.payment.eghl')->with($data);
    }

    public function callback(Request $request)
    {
        Log::info('Received Request from Payment Gateway: ' . json_encode($request->input()));
        
        EGHLLog::where('payment_id', $request->input('PaymentID'))
            ->update([
                'payment_method' => $request->input('PymtMethod'),
                'txn_status'=> $request->input('TxnStatus'),
                'txn_message' => $request->input('TxnMessage'),
                'response_hash' => $request->input('HashValue2'),
                'issuing_bank' => $request->input('IssuingBank'),
                'bank_reference' => $request->input('TxnID'),
                'auth_code' => $request->input('AuthCode'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        // verify hash value 2
        $return_hash = [
            config('setting.payment.eghl.merchant_password'),
            $request->input('TxnID'),
            config('setting.payment.eghl.merchant_id'),
            $request->input('PaymentID'),
            $request->input('TxnStatus'),
            $request->input('Amount'),
            $request->input('CurrencyCode'),
            $request->input('AuthCode'),
            $request->input('OrderNumber'),
            $request->input('Param6'),
            $request->input('Param7'),
        ];

        $hash_value_2 = hash('sha256', implode('', $return_hash));

        if ($hash_value_2 != $request->input('HashValue2')) {
            return  'Invalid Hash Value';
        }

        // get insurance record
        $insurance_code = Str::beforeLast($request->input('PaymentID'), '-');
        $insurance = Insurance::findByInsuranceCode($insurance_code);

        // check transaction status
        // 0 - Transaction successful
        // 1 - Transaction failed
        // 2 - Transaction pending
        switch ($request->input('TxnStatus')) {
            case '0': {
                if ($insurance->insurance_status == Insurance::STATUS_NEW_QUOTATION || $insurance->insurance_status == Insurance::STATUS_PAYMENT_FAILURE) {
                    if (floatval($insurance->total_payable) != floatval($request->input('Amount'))) {
                        // Update Insurance Status
                        Insurance::find($insurance->id)
                            ->update([
                                'insurance_status' => Insurance::STATUS_PAYMENT_FAILURE
                            ]);

                        // Create Remark
                        InsuranceRemark::create([
                            'insurance_id' => $insurance->id,
                            'remark' => __('api.total_payable_not_match') . '. (Payment ID: ' . $request->input('PaymentID') . ')'
                        ]);

                        return __('api.total_payable_not_match');
                    }

                    // Update Insurance Status
                    Insurance::find($insurance->id)
                        ->update([
                            'insurance_status_id' => Insurance::STATUS_PAYMENT_ACCEPTED
                        ]);

                    // Create Remark
                    InsuranceRemark::create([
                        'insurance_id' => $insurance->id,
                        'remark' => 'Payment accepted. (Payment ID: ' . $request->input('PaymentID') . ')'
                    ]);

                    // Policy Submission
                    $data = (object) [
                        'insurance_code' => $insurance->insurance_code,
                        'id_number' => $insurance->policy_holder->ic_number,
                        'payment_method' => $request->input('PymtMethod'),
                        'payment_amount' => $request->input('Amount'),
                        'payment_date' => Carbon::now()->toDateTimeString(),
                        'transaction_reference' => $request->input('PaymentID'),
                        'vehicle_numnber' => $insurance->motor->vehicle_number
                    ];

                    $controller = new MotorAPIController();
                    $result = $controller->submitCoverNote($data);

                    if (!$result->status) {
                        return $result->response;
                    }
                }

                break;
            }
            case '1': {
                if ($insurance->insurance_status == Insurance::STATUS_NEW_QUOTATION || $insurance->insurance_status == Insurance::STATUS_PAYMENT_FAILURE) {
                    // Update Insurance Status
                    Insurance::find($insurance->id)
                        ->update([
                            'insurance_status' => Insurance::STATUS_PAYMENT_FAILURE
                        ]);

                    InsuranceRemark::create([
                        'insurance_id' => $insurance->id,
                        'remark' => $request->input('TxnMessage') . '. (Payment ID: ' . $request->input('PaymentID') . ')'
                    ]);
                }

                break;
            }
        }

        return 'OK';
    }
}