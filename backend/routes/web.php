<?php

use App\Http\Controllers\Admin\AdminPaymentSlipController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentSlipController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home');
})->name('home');

Route::view('/shop', 'pages.shop')->name('shop');
Route::view('/contact', 'pages.contact')->name('contact');

Route::middleware('firebase.auth')->group(function (): void {
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/payment', [PaymentSlipController::class, 'create'])->name('orders.payment.create');
    Route::post('/orders/{order}/payment-slip', [PaymentSlipController::class, 'store'])->name('orders.payment-slips.store');
});

Route::middleware(['firebase.auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/payment-slips', [AdminPaymentSlipController::class, 'index'])->name('payment-slips.index');
        Route::get('/payment-slips/{paymentSlip}', [AdminPaymentSlipController::class, 'show'])->name('payment-slips.show');
        Route::post('/payment-slips/{paymentSlip}/approve', [AdminPaymentSlipController::class, 'approve'])->name('payment-slips.approve');
        Route::post('/payment-slips/{paymentSlip}/reject', [AdminPaymentSlipController::class, 'reject'])->name('payment-slips.reject');
    });
