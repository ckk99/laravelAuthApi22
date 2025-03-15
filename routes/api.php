<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MerchantPaymentController;
use App\Http\Controllers\ResellerPaymentController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });



Route::post('login', [AuthController::class, 'login']);
Route::post('send-login-otp', [AuthController::class, 'sendLoginOtp']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('profile', [AuthController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);

    
});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('merchant-details', [AdminController::class, 'merchantFetchIndividual']);
        Route::post('merchant-complete-kyc', [AdminController::class, 'merchantDueDeligence']);
        Route::post('merchant-onboarding', [AdminController::class, 'merchantCompleteOnboarding']);
        Route::get('merchant-transactions', [AdminController::class, 'transactionDetails']);
        
    });
});

// Protect user-specific routes with the 'user' role
Route::middleware(['auth:sanctum', 'role:user'])->group(function () {
    // Route::prefix('user')->group(function () {
    //     // Add other user-specific routes here
    // });
    Route::prefix('merchant')->group(function () {
        Route::get('auth-token', [ResellerPaymentController::class, 'generateToken']);
        Route::post('create-payment-request', [ResellerPaymentController::class, 'createPaymentRequest']);
        Route::post('callback', [ResellerPaymentController::class, 'callback']);
        Route::post('create-collect-request', [ResellerPaymentController::class, 'createCollectRequest']);
        Route::post('payment-status', [ResellerPaymentController::class, 'checkPaymentStatus']);
        Route::get('transaction-details', [ResellerPaymentController::class, 'transactionDetails']);
        
    });

});



// Route::post('credit-report', [TestController::class, 'getCreditReport']);
// Route::post('experian-report', [TestController::class, 'getExperianReport']);
// Route::post('countries', [TestController::class, 'getCountries']);
// Route::post('states', [TestController::class, 'getStates']);
// Route::post('cities', [TestController::class, 'getCities']);
// Route::get('validate-bank', [TestController::class, 'validateBank']);

