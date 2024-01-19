<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Insurance\MotorController;
use App\Http\Controllers\PaymentController;
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
|    
*/

Route::middleware(['web', 'cors'])->group(function() {
    Route::any('{any}', function () {
        return view('maintenance'); // Replace 'maintenance' with the actual view name or route
    })->where('any', '.*');
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
            Route::get('/faq', [HomeController::class, 'faq'])->name('motor.faq');
            Route::get('/', [MotorController::class, 'index'])->name('motor.index');
            Route::post('/', [MotorController::class, 'index_POST']);
            Route::get('/vehicle-details', [MotorController::class, 'vehicleDetails'])->name('motor.vehicle-details');
            Route::post('/vehicle-details', [MotorController::class, 'vehicleDetails_POST']);
            Route::get('/compare', [MotorController::class, 'compare'])->name('motor.compare');
            Route::post('/compare', [MotorController::class, 'compare_POST']);
            Route::get('/add-ons', [MotorController::class, 'addOns'])->name('motor.add-ons');
            Route::post('/add-ons', [MotorController::class, 'addOns_POST']);
            Route::get('/policy-holder', [MotorController::class, 'policyHolder'])->name('motor.policy-holder');
            Route::post('/policy-holder', [MotorController::class, 'policyHolder_POST']);
            Route::get('/payment/summary', [MotorController::class, 'paymentSummary'])->name('motor.payment-summary');
            Route::get('/payment/success', [MotorController::class, 'paymentSuccess'])->name('motor.payment-success');
            Route::get('/payment/failed', [MotorController::class, 'paymentFailure'])->name('motor.payment-failed');
        }
    );
});

Route::group(['prefix' => 'payment'], function() {
    Route::post('/insurance', [PaymentController::class, 'store'])->name('payment.store');
    Route::match(['get', 'post'], '/callback', [PaymentController::class, 'callback'])->name('payment.callback');
});

// Redirects
Route::middleware(['web'])->group(function() {
    Route::redirect('/howden', 'https://www.howdengroup.com/my-en')->name('howden_website');
    Route::redirect('/blog', 'https://blog.instapol.my/')->name('instapol_blog');
    Route::redirect('/login', 'https://howden-account-dev.instapol.my')->name('dashboard');
    Route::redirect('/motor-extended', config('setting.redirects.motor_extended'));
    Route::redirect('/bike', config('setting.redirects.bicycle'));
    Route::redirect('/travel', config('setting.redirects.travel'));
    Route::redirect('/doc-pro', config('setting.redirects.doc_pro'));
    Route::redirect('/sme', config('setting.redirects.sme'));
    Route::redirect('/hho', config('setting.redirects.hho'));
    Route::redirect('/landlord', config('setting.redirects.landlord'));
    Route::redirect('/miea', config('setting.redirects.miea'));
    Route::redirect('/pickles', config('setting.redirects.pickles'));
});