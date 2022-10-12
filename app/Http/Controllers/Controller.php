<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function abort($message = 'An error encountered.', int $code = 500, array $response_header = [])
    {
        return response()->json(['status' => false, 'response' => $message, 'code' => $code], $code, $response_header);
    }
}
