<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Insurance\MotorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::middleware(['web'])->group(function() {
    // General Routes
    Route::get('/', [HomeController::class, 'index'])->name('frontend.index');
    Route::get('/about-us', [HomeController::class, 'aboutUs'])->name('frontend.about-us');
    Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('frontend.privacy');
    Route::get('/cookie-policy', [HomeController::class, 'cookiePolicy'])->name('frontend.cookie');
    Route::get('/refund-policy', [HomeController::class, 'refundPolicy'])->name('frontend.refund');
    Route::get('/term-of-use', [HomeController::class, 'termsAndConditions'])->name('frontend.term-of-use');
    Route::get('/claims', [HomeController::class, 'claims'])->name('frontend.claims');

    Route::group(
        [
            'prefix' => 'motor',
            'middleware' => ['web']
        ], function () {
            Route::get('/', [MotorController::class, 'index'])->name('motor.index');
            Route::post('/', [MotorController::class, 'index_POST'])->name('motor.index-post');
            Route::post('/vehicle-details', [MotorController::class, 'vehicleDetails'])->name('motor.vehicle-details');
        }
    );
});

// Redirects
Route::middleware(['web'])->group(function() {
    Route::redirect('/howden', 'https://www.howdengroup.com/my-en')->name('howden_website');
    Route::redirect('/blog', 'https://blog.instapol.my/')->name('instapol_blog');
});