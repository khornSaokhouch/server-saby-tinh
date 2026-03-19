<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BakongController;
use App\Http\Controllers\TestaQr;
use App\Http\Controllers\AbaPaywayController;

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

// ABA PayWay Test
use App\Http\Controllers\TestingAbaController;
Route::get('/aba-test', [TestingAbaController::class, 'index'])->name('aba.test');
Route::get('/aba-test/check', [TestingAbaController::class, 'checkTransactionV2'])->name('aba.test.check');
Route::get('/aba-test/close', [TestingAbaController::class, 'closeTransaction'])->name('aba.test.close');
Route::get('/aba-test/purchase', [TestingAbaController::class, 'purchase'])->name('aba.test.purchase');
Route::get('/aba-test/generate-qr', [TestingAbaController::class, 'generateQr'])->name('aba.test.generate-qr');
Route::post('/aba-test/refund', [TestingAbaController::class, 'refund'])->name('aba.test.refund');
