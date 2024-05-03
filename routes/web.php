<?php

use App\Http\Controllers\RedirectPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('payment-finish', [RedirectPaymentController::class, 'finish']);
