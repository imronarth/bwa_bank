<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\TopUpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::group(['middleware' => 'jwt.verify'], function ($route) {
    Route::post('top-ups', [TopUpController::class, 'store']);
});
Route::post('webhook', [WebhookController::class, 'update']);
// Route::middleware('jwt.verify')->get('test', function (Request $request) {
//     return 'success';
// });
