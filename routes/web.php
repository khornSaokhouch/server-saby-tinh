<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BakongController;
use App\Http\Controllers\TestaQr;

// Home
Route::get('/', function () {
    return view('welcome');
});

// Show Bakong form
Route::get('/bakong-payment', [BakongController::class, 'showForm'])
    ->name('bakong.payment-form');

// Generate QR code
Route::post('/bakong-payment', [BakongController::class, 'generateQr'])
    ->name('bakong.generate-qr');

// Check MD5
Route::post('/check-md5', [BakongController::class, 'checkMd5'])
    ->name('bakong.check-md5');

// Test QR
Route::get('/test-qr', [TestaQr::class, 'generateQr'])
    ->name('test.qr');