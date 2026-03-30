<?php

use Illuminate\Support\Facades\Route;



use App\Http\Controllers\BakongController;


// Home
Route::get('/', function () {
    return view('welcome');
});



/*
|--------------------------------------------------------------------------
| TEST ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/bakong', [BakongController::class, 'form']);
Route::post('/bakong/generate', [BakongController::class, 'generateQr']);

// ABA Payway Test Routes
use App\Http\Controllers\AbaPaywayController;

Route::get('/aba-payway', [AbaPaywayController::class, 'index'])->name('payway.index');
Route::prefix('aba-payway')->group(function () {
    Route::get('/products', [AbaPaywayController::class, 'showForm']);
    Route::post('/generate-qr', [AbaPaywayController::class, 'generateQr']);
    Route::post('/checkout', [AbaPaywayController::class, 'checkout']);
    Route::post('/add-card', [AbaPaywayController::class, 'initialAddCard']);
    Route::post('/check-transaction', [AbaPaywayController::class, 'checkTransaction']);
});