<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('pay', [PaymentController::class, 'index'])->name('pay');
Route::post('pay', [PaymentController::class, 'makePayment'])->name('pay.make');
Route::get('pay/callback', [PaymentController::class, 'paymentCallback'])->name('pay.callback');