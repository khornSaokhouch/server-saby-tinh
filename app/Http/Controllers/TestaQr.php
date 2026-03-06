<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class TestaQr extends Controller
{
    public function generateQr()
    {
        $individualInfo = new IndividualInfo(
            bakongAccountID: 'khorn_saokhouch@bkrt',
            merchantName: 'Test Merchant',
            merchantCity: 'PHNOM PENH',
            currency: KHQRData::CURRENCY_KHR,
            amount: 1000,
            billNumber: 'TEST-123456'
        );

        $response = BakongKHQR::generateIndividual($individualInfo);

        return response()->json($response);
    }
}