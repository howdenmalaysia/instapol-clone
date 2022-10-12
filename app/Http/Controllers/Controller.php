<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function checkMotorSessionObject(Request $request)
    {
        if(empty($request->session()->get('motor'))) {
            return redirect()->route('motor.index');
        }

        return $request->session()->get('motor');
    }

    public function abort($message = 'An error encountered.', int $code = 500, array $response_header = [])
    {
        return response()->json(['status' => false, 'response' => $message, 'code' => $code], $code, $response_header);
    }
}
