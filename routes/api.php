<?php

use App\Http\Controllers\v1\Sms\SmsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware('auth.merchant')->group(function () {
    Route::post('/cost', [SmsController::class, 'estimateCost']);
    Route::get('/balance', [SmsController::class, 'walletBalance']);
});
