<?php

namespace App\Http\Controllers;

use App\Helpers\Submission;
use App\Http\Controllers\API\MotorAPIController;
use App\Models\EGHLLog;
use App\Models\InsuranceRemark;
use App\Models\Motor\Insurance;
use App\Models\Motor\Product;
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

        // Get Product Details
        $product = Product::with(['product_type'])
            ->where('id', $insurance->product_id)
            ->first();

        if(empty($insurance)) {
            return __('api.insurance_record_not_match');
        }

        if(round($insurance->amount, 2) !== round($request->total_payable, 2)) {
            return back()->with(['flash_danger' => __('api.total_payable_not_match') ]);
        }

        $payment_attempts = EGHLLog::where('payment_id', 'LIKE', '%' . $insurance->insurance_code. '%')->count();
        $payment_id = generatePaymentID($payment_attempts, $insurance->insurance_code);
        $payment_description = $insurance->product->insurance_company->name . ' - ' . $insurance->product->name;

        if(!empty($request->description)) {
            $payment_description .= ', ' . $request->description;
        }

        $merchant_id = $merchant_password = '';
        switch($insurance->holder->id_type_id) {
            case config('setting.id_type.company_registration_no'): {
                $merchant_id = config('setting.payment.gateway.fpx_merchant_id');
                $merchant_password = config('setting.payment.gateway.fpx_merchant_password');

                break;
            }
            default: {
                $merchant_id = config('setting.payment.gateway.merchant_id');
                $merchant_password = config('setting.payment.gateway.merchant_password');
            }
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
            'ip' => route('frontend.index'),
            'customer_name' => $insurance->holder->name,
            'customer_email' => $insurance->holder->email_address,
            'customer_phone_number' => $insurance->holder->phone_number,
            'language' => 'en',
            'timeout' => 780,
            'param6' => Str::snake(Str::lower(str_replace('-', '', $product->product_type->description))) . '-' . $product->name,
            'merchant_id' => $merchant_id
        ];

        $hash = [
            $merchant_password,
            $merchant_id,
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
            'hash' => $data['hash'],
        ]);

        Log::info("Request Logged Successfully.");

        return view('frontend.payment.eghl')->with($data);
    }

    public function callback(Request $request)
    {
        Log::info('Received Request from Payment Gateway: ' . json_encode($request->input()));
        
        EGHLLog::where('payment_id', $request->PaymentID)
            ->update([
                'payment_method' => $request->PymtMethod,
                'txn_status'=> $request->TxnStatus,
                'txn_message' => $request->TxnMessage,
                'response_hash' => $request->HashValue2,
                'issuing_bank' => $request->IssuingBank,
                'bank_reference' => $request->TxnID,
                'auth_code' => $request->AuthCode,
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        // verify hash value 2
        $return_hash = [
            config('setting.payment.gateway.merchant_password'),
            $request->TxnID,
            config('setting.payment.gateway.merchant_id'),
            $request->PaymentID,
            $request->TxnStatus,
            $request->Amount,
            $request->CurrencyCode,
            $request->AuthCode,
            $request->OrderNumber,
            $request->Param6,
            $request->Param7,
        ];

        $hash_value_2 = hash('sha256', implode('', $return_hash));

        if ($hash_value_2 != $request->HashValue2) {
            return  'Invalid Hash Value';
        }

        // get insurance record
        $insurance_code = Str::beforeLast($request->PaymentID, '-');
        $insurance = Insurance::findByInsuranceCode($insurance_code);

        // check transaction status
        // 0 - Transaction successful
        // 1 - Transaction failed
        // 2 - Transaction pending
        switch ($request->TxnStatus) {
            case '0': {
                if ($insurance->insurance_status == Insurance::STATUS_NEW_QUOTATION || $insurance->insurance_status == Insurance::STATUS_PAYMENT_FAILURE) {
                    if (floatval($insurance->amount) != floatval($request->Amount)) {
                        // Update Insurance Status
                        Insurance::where('id', $insurance->id)
                            ->update([
                                'insurance_status' => Insurance::STATUS_PAYMENT_FAILURE
                            ]);

                        // Create Remark
                        InsuranceRemark::create([
                            'insurance_id' => $insurance->id,
                            'remark' => __('api.total_payable_not_match') . '. (Payment ID: ' . $request->PaymentID . ')'
                        ]);

                        return __('api.total_payable_not_match');
                    }

                    // Update Insurance Status
                    Insurance::where('id', $insurance->id)
                        ->update([
                            'insurance_status' => Insurance::STATUS_PAYMENT_ACCEPTED
                        ]);

                    // Create Remark
                    InsuranceRemark::create([
                        'insurance_id' => $insurance->id,
                        'remark' => 'Payment accepted. (Payment ID: ' . $request->PaymentID . ')'
                    ]);

                    // Policy Submission
                    $data = (object) [
                        'insurance_code' => $insurance->insurance_code,
                        'id_number' => $insurance->holder->id_number,
                        'payment_method' => $request->PymtMethod,
                        'payment_amount' => $request->Amount,
                        'payment_date' => Carbon::now()->format('Y-m-d H:i:s'),
                        'transaction_reference' => $request->PaymentID,
                        'vehicle_number' => $insurance->motor->vehicle_number
                    ];

                    $helper = new Submission(Str::before($request->Param6, '-'), Str::after($request->Param6, '-'));
                    $helper->submission($data);
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
                        'remark' => $request->TxnMessage . '. (Payment ID: ' . $request->PaymentID . ')'
                    ]);
                }

                break;
            }
        }

        $request->session()->put(Str::before($request->Param6, '-'), $insurance->insurance_code);

        return redirect()->route(Str::before($request->Param6, '-') . '.payment-success');
    }
}
