<?php

use App\Http\Controllers\API\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
// Route::middleware('throttle:3,10')->group(function () {
    Route::controller(RegisterController::class)->group(function () {
        Route::post('/register', 'register');
    });
// });
Route::middleware('auth:sanctum')->group(function () {
    Route::controller(RegisterController::class)->group(function () {
        Route::post('/check_otp', 'checkOtp');
    });
});
