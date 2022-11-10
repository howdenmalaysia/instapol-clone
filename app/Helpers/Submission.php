<?php

namespace App\Helpers;

use App\DataTransferObjects\Motor\Response\ResponseData;

class Submission
{
    private string $insurer = '';
    private string $domain = '';
    private string $product_type = '';

    public function __construct(string $product_type, string $insurer)
    {
        $this->domain = config('app.url');
        $this->product_type = $product_type;
        $this->insurer = $insurer;
    }

    public function submission(object $data) : object
    {
        $path = '';
        $form = [];

        switch($this->product_type) {
            case 'motor': {
                $path = '/motor/submit-cover-note';

                $param = (object) [
                    'insurance_code' => $data->insurance_code,
                    'payment_method' => $data->payment_method,
                    'payment_amount' => $data->payment_amount,
                    'payment_date' => $data->payment_date,
                    'product_id' => $data->product_id
                ];

                break;
            }
        }

        $result = $this->cURL($path, $param);
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
        $result = HttpClient::curl($method, $this->domain . '/api' . $path, $request_options);

        $response = json_decode($result->response);

        if($result->status) {

        } else {
            if(empty($response)) {
                $message = __('api.empty_response', ['company' => $this->insurer]);
            } else {
                $message = 'An Error Encountered. ' . json_encode($response);
            }

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