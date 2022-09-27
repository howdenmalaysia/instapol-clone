<?php

use App\Http\Controllers\API\MotorAPIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('/motor')->group(function() {
    Route::post('/vehicle-details', [MotorAPIController::class, 'getVehicleDetails'])->name('motor.api.vehicle-details');
    Route::post('/quote/{quote_type?}', [MotorAPIController::class, 'getQuote'])->name('motor.api.quote');
    Route::post('/create-quotation', [MotorAPIController::class, 'createQuotation'])->name('motor.api.create-quotation');
    Route::post('/submit-cover-note', [MotorAPIController::class, 'submitCoverNote'])->name('motor.api.submit-cover-note');
});
