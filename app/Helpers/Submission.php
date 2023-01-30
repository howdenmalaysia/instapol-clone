<?php

namespace App\Helpers;

use App\DataTransferObjects\Motor\Response\ResponseData;
use Illuminate\Support\Facades\Log;

class Submission
{
    private string $insurer;
    private string $domain;
    private string $product_type;

    public function __construct(string $product_type, string $insurer)
    {
        $this->domain = config('app.url');
        $this->product_type = $product_type;
        $this->insurer = $insurer;
    }

    public function submission(object $data) : object
    {
        Log::info("[Submission] Received Submission Request for {$data->insurance_code}. " . json_encode($data));

        $path = '';
        $param = (object) [];

        switch($this->product_type) {
            case 'motor': {
                $path = route('motor.api.submit-cover-note');

                $param = (object) [
                    'insurance_code' => $data->insurance_code,
                    'payment_method' => $data->payment_method,
                    'payment_amount' => $data->payment_amount,
                    'payment_date' => $data->payment_date
                ];

                break;
            }
        }

        $result = $this->cURL($path, $param);
        Log::info("[Submission] Received Submission Response from Insurer for {$data->insurance_code}. " . json_encode($result->response));
        
        if(!$result->status) {
            return $this->abort($result->response);
        }

        return (object) [
            'status' => true,
            'response' => $result->response
        ];
    }

    public function cURL(string $path, object $data, string $method = 'POST')
    {
        $request_options = [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'json' => $data
        ];

        // API Call
        $result = HttpClient::curl($method, $path, $request_options);

        $response = json_decode($result->response);

        if(!$result->status) {
            if(empty($response)) {
                $message = __('api.empty_response', ['company' => $this->insurer]);
            } else {
                $message = 'An Error Encountered. ' . json_encode($response);
            }

            Log::error("[Submission] {$message}");

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
}