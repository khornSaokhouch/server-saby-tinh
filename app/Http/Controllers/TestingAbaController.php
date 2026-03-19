<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestingAbaController extends Controller
{
    private string $merchantId;
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        // Forcing Official ABA Test Merchant for guaranteed Popup success
        $this->merchantId = 'ec000001';
        $this->apiKey     = '700940398328aa9c1e7a052846387063';
        $this->baseUrl    = 'https://checkout-sandbox.payway.com.kh';
    }

    private function sign($data): string
    {
        return base64_encode(hash_hmac('sha512', $data, $this->apiKey, true));
    }

    public function index()
    {
        // 1. Time must be current for KHQR/Checkout
        $req_time = now()->format('YmdHis'); 

        // 2. Transaction ID must be unique EVERY refresh to avoid hangs
        $tran_id  = 'T' . date('his') . rand(100, 999); 

        $amount   = '0.10';
        $firstname = 'Khorn';
        $lastname  = 'Saokhouch';
        $phone     = '0964415022';
        $email     = 'khornsaokhouch4456@gmail.com';

        // 3. Official test merchant standard options
        $payment_option = 'cards,abapay';
        $currency       = 'USD';

        // 4. Important: Sandbox requires these to be blank or match EXACTLY for local testing
        $return_url           = '';
        $cancel_url           = '';
        $continue_success_url = '';
        $custom_fields        = '';
        $return_params        = '';

        // Order for hashing (Checkout 2.0)
        $hashData = $req_time . $this->merchantId . $tran_id . $amount . $firstname . $lastname . $email . $phone . $payment_option . $return_url . $cancel_url . $continue_success_url . $currency . $custom_fields . $return_params;
        
        $hash = $this->sign($hashData);

        return view('payway-test', [
            'hash'                 => $hash,
            'tran_id'              => $tran_id,
            'amount'               => $amount,
            'merchant_id'          => $this->merchantId,
            'req_time'             => $req_time,
            'firstname'            => $firstname,
            'lastname'             => $lastname,
            'phone'                => $phone,
            'email'                => $email,
            'currency'             => $currency,
            'payment_option'       => $payment_option,
            'return_url'           => $return_url,
            'cancel_url'           => $cancel_url,
            'continue_success_url' => $continue_success_url,
            'custom_fields'        => $custom_fields,
            'return_params'        => $return_params,
            'base_url'             => $this->baseUrl,
        ]);
    }

    public function purchase(Request $request)
    {
        $reqTime  = now()->format('YmdHis');
        $tranId   = (string) time();
        $amount   = '0.10';
        $firstname = 'Khorn';
        $lastname  = 'Saokhouch';
        $phone     = '0964415022';
        $email     = 'khornsaokhouch4456@gmail.com';
        $paymentOption = 'abapay_khqr';
        $currency      = 'USD';
        $returnUrl     = url('/aba-test');
        $cancelUrl     = url('/aba-test');
        $continueSuccessUrl = url('/aba-test');
        $customFields  = '';
        $returnParams  = '';

        // Checkout 2.0 / purchase hash order
        $hashData = $reqTime . $this->merchantId . $tranId . $amount . $firstname . $lastname . $email . $phone . $paymentOption . $returnUrl . $cancelUrl . $continueSuccessUrl . $currency . $customFields . $returnParams;
        $hash = $this->sign($hashData);

        $payload = [
            'req_time'             => $reqTime,
            'merchant_id'          => $this->merchantId,
            'tran_id'              => $tranId,
            'amount'               => $amount,
            'firstname'            => $firstname,
            'lastname'             => $lastname,
            'phone'                => $phone,
            'email'                => $email,
            'payment_option'       => $paymentOption,
            'currency'             => $currency,
            'return_url'           => $returnUrl,
            'cancel_url'           => $cancelUrl,
            'continue_success_url' => $continueSuccessUrl,
            'custom_fields'        => $customFields,
            'return_params'        => $returnParams,
            'hash'                 => $hash,
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->post("{$this->baseUrl}/api/payment-gateway/v1/payments/purchase", $payload);

            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function checkTransactionV2(Request $request)
    {
        $tranId  = $request->query('tran_id');
        if (!$tranId) {
            return response()->json(['success' => false, 'message' => 'tran_id is required'], 400);
        }

        $reqTime = now()->format('YmdHis');
        
        // Correct V2 Hash order: req_time + merchant_id + tran_id
        $hashData = $reqTime . $this->merchantId . $tranId;
        $hash = $this->sign($hashData);

        $payload = [
            'req_time'    => (string) $reqTime,
            'merchant_id' => (string) $this->merchantId,
            'tran_id'     => (string) $tranId,
            'hash'        => (string) $hash,
        ];

        try {
            // Send JSON directly (no base64 request wrapper)
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ])->post("{$this->baseUrl}/api/payment-gateway/v1/payments/check-transaction-2", $payload);

            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function closeTransaction(Request $request)
    {
        $tranId = $request->query('tran_id');
        if (!$tranId) {
            return response()->json(['success' => false, 'message' => 'tran_id is required'], 400);
        }

        $reqTime = now()->format('YmdHis');

        // Hash: req_time + merchant_id + tran_id
        $hashData = $reqTime . $this->merchantId . $tranId;
        $hash = $this->sign($hashData);

        $payload = [
            'req_time'    => (string) $reqTime,
            'merchant_id' => (string) $this->merchantId,
            'tran_id'     => (string) $tranId,
            'hash'        => (string) $hash,
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ])->post("{$this->baseUrl}/api/payment-gateway/v1/payments/close-transaction", $payload);

            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function generateQr(Request $request)
    {
        $currency = $request->query('currency', 'USD');
        $amount   = ($currency === 'KHR') ? '400' : '0.10';
        
        $reqTime = now()->format('YmdHis');
        $tranId  = 'QR' . time();
        $firstName = 'Khorn';
        $lastName  = 'Saokhouch';
        $email     = 'khornsaokhouch4456@gmail.com';
        $phone     = '0964415022';
        $paymentOption = 'abapay_khqr';
        $qrImageTemplate = 'template3_color';
        
        $itemsBase64 = base64_encode(json_encode([['name' => 'Testing', 'quantity' => 1, 'price' => $amount]]));
        
        // 18 Params for Hash
        $hashData = $reqTime . $this->merchantId . $tranId . $amount . $itemsBase64 . $firstName . $lastName . $email . $phone . 'purchase' . $paymentOption . '' . '' . $currency . '' . '' . '45' . $qrImageTemplate;
        $hash = $this->sign($hashData);

        $payload = [
            'req_time'          => (string) $reqTime,
            'merchant_id'       => (string) $this->merchantId,
            'tran_id'           => (string) $tranId,
            'amount'            => (string) $amount,
            'first_name'        => (string) $firstName,
            'last_name'         => (string) $lastName,
            'email'             => (string) $email,
            'phone'             => (string) $phone,
            'items'             => (string) $itemsBase64,
            'payment_option'    => (string) $paymentOption,
            'currency'          => (string) $currency,
            'purchase_type'     => 'purchase',
            'lifetime'          => 45,
            'qr_image_template' => (string) $qrImageTemplate,
            'hash'              => (string) $hash,
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ])->post("{$this->baseUrl}/api/payment-gateway/v1/payments/generate-qr", [
                'request' => base64_encode(json_encode($payload))
            ]);
            
            if ($response->status() === 415 || $response->json('status.code') == '04') {
                 $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json'
                ])->post("{$this->baseUrl}/api/payment-gateway/v1/payments/generate-qr", $payload);
            }

            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function refund(Request $request)
    {
        $tranId = $request->input('tran_id');
        $amount = $request->input('amount', '0.10');
        
        if (!$tranId) {
            return response()->json(['success' => false, 'message' => 'tran_id is required'], 400);
        }

        $reqTime = now()->format('YmdHis');
        
        // Refund Hash: merchant_id + req_time + tran_id + amount
        $hashData = $this->merchantId . $reqTime . $tranId . $amount;
        $hash = $this->sign($hashData);

        $payload = [
            'merchant_id' => (string) $this->merchantId,
            'req_time'    => (string) $reqTime,
            'tran_id'     => (string) $tranId,
            'amount'      => (string) $amount,
            'hash'        => (string) $hash,
            'refund_reason' => 'Testing'
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ])->post("{$this->baseUrl}/api/merchant-portal/merchant-access/online-transaction/refund", [
                'request' => base64_encode(json_encode($payload))
            ]);
            
            if ($response->status() === 415 || $response->json('status.code') == '04') {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json'
                ])->post("{$this->baseUrl}/api/merchant-portal/merchant-access/online-transaction/refund", $payload);
            }

            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
