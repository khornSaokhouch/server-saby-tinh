<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AbaPaywayController extends Controller
{
    private string $merchantId;
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('services.payway.merchant_id');
        $this->apiKey     = config('services.payway.api_key');
        $this->baseUrl    = config('services.payway.base_url');
    }

    private function reqTime(): string
    {
        return now()->format('YmdHis');
    }

    private function sign(array $values): string
    {
        $data = implode('', $values);
        return base64_encode(hash_hmac('sha512', $data, $this->apiKey, true));
    }

    public function showForm()
    {
        $products = Product::select('id', 'name', 'price')->orderBy('name')->get();

        return response()->json([
            'success'    => true,
            'merchantId' => $this->merchantId,
            'products'   => $products,
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'tran_id'         => 'required|string',
            'product_ids'     => 'required|array|min:1',
            'product_ids.*'   => 'exists:products,id',
            'currency'        => 'required|in:USD,KHR',
            'payment_option'  => 'required|string',
        ]);

        $selectedProducts = Product::whereIn('id', $request->product_ids)->get();
        $totalAmount = $selectedProducts->sum(fn($p) => (float) $p->price);
        
        $reqTime  = $this->reqTime();
        $tranId   = $request->tran_id;
        $currency = $request->currency;
        $amount   = ($currency === 'KHR') ? number_format($totalAmount, 0, '.', '') : number_format($totalAmount, 2, '.', '');
        
        $firstName = 'Khorn';
        $lastName  = 'saokhouch';
        $email     = 'khornsaokhouch4456@gmail.com';
        $phone     = '0964415022';
        $paymentOption = $request->payment_option; // 'abapay', 'cards', or 'abapay,cards'
        
        $returnUrl = $request->return_url ?? route('payway.index');
        $cancelUrl = $request->cancel_url ?? route('payway.index');
        $continueSuccessUrl = $request->continue_success_url ?? route('payway.index');
        
        // Hash Order for Purchase/Checkout: 
        // req_time + merchant_id + tran_id + amount + firstname + lastname + email + phone + payment_option + return_url + cancel_url + continue_success_url + currency + custom_fields + return_params
        $hashData = [
            $reqTime,
            $this->merchantId,
            $tranId,
            $amount,
            $firstName,
            $lastName,
            $email,
            $phone,
            $paymentOption,
            $returnUrl,
            $cancelUrl,
            $continueSuccessUrl,
            $currency,
            '', // custom_fields
            '', // return_params
        ];
        
        $hash = $this->sign($hashData);
        
        $data = [
            'req_time'             => $reqTime,
            'merchant_id'          => $this->merchantId,
            'tran_id'              => $tranId,
            'amount'               => $amount,
            'firstname'            => $firstName,
            'lastname'             => $lastName,
            'email'                => $email,
            'phone'                => $phone,
            'payment_option'       => $paymentOption,
            'currency'             => $currency,
            'return_url'           => base64_encode($returnUrl),
            'cancel_url'           => base64_encode($cancelUrl),
            'continue_success_url' => base64_encode($continueSuccessUrl),
            'hash'                 => $hash,
        ];

        // This endpoint returns a redirection form or URL
        return response()->json([
            'success' => true,
            'url'     => "{$this->baseUrl}/api/payment-gateway/v1/payments/purchase",
            'params'  => $data
        ]);
    }

    public function initialAddCard(Request $request)
    {
        $reqTime = $this->reqTime();
        $ctid    = 'CTID-' . time(); // Unique customer transaction ID
        
        $firstName = $request->first_name ?? 'Khorn';
        $lastName  = $request->last_name ?? 'saokhouch';
        $email     = $request->email ?? 'khornsaokhouch4456@gmail.com';
        $phone     = $request->phone ?? '0964415022';
        $returnUrl    = $request->return_url ?? route('payway.index');
        $returnParams = $request->return_params ?? 'rp-' . time();

        // Hash Order for COF Initial: 
        // req_time + merchant_id + ctid + firstname + lastname + email + phone + return_url + return_params
        $hashData = [
            $reqTime,
            $this->merchantId,
            $ctid,
            $firstName,
            $lastName,
            $email,
            $phone,
            $returnUrl,
            $returnParams,
        ];

        $hash = $this->sign($hashData);

        $payload = [
            'req_time'      => $reqTime,
            'merchant_id'   => $this->merchantId,
            'ctid'          => $ctid,
            'firstname'     => $firstName,
            'lastname'      => $lastName,
            'email'         => $email,
            'phone'         => $phone,
            'return_url'    => $returnUrl,
            'return_params' => $returnParams,
            'hash'          => $hash,
        ];

        Log::info('PayWay Initial Add Card Request (For SDK)', $payload);

        return response()->json([
            'success'      => true,
            'merchant_id'  => $this->merchantId,
            'req_time'     => $reqTime,
            'ctid'         => $ctid,
            'firstname'    => $firstName,
            'lastname'     => $lastName,
            'email'        => $email,
            'phone'        => $phone,
            'return_url'   => $returnUrl,
            'return_params'=> $returnParams,
            'hash'         => $hash,
            'action_url'   => "{$this->baseUrl}/api/payment-gateway/v1/cof/initial?lang=en",
        ]);
    }

    public function generateQr(Request $request)
    {
        $request->validate([
            'tran_id'         => 'required|string',
            'product_ids'     => 'required|array|min:1',
            'product_ids.*'   => 'exists:products,id',
            'currency'        => 'required|in:USD,KHR',
            'payment_option'  => 'required|string',
        ]);

        $selectedProducts = Product::whereIn('id', $request->product_ids)->get();

        $itemsArray = $selectedProducts->map(fn($p) => [
            'name'     => (string) $p->name,
            'quantity' => 1,
            'price'    => number_format((float) $p->price, 2, '.', ''),
        ])->values()->toArray();

        $totalAmount = $selectedProducts->sum(fn($p) => (float) $p->price);

        $reqTime = $this->reqTime();
        $tranId  = $request->tran_id;
        $currency = $request->currency ?? 'USD';
        
        // KHQR Guideline: No decimals for KHR
        $amount = ($currency === 'KHR') 
            ? number_format($totalAmount, 0, '.', '') 
            : number_format($totalAmount, 2, '.', '');

        $itemsBase64 = base64_encode(json_encode($itemsArray, JSON_UNESCAPED_SLASHES));

        $paymentOption = $request->payment_option ?? 'abapay';
        $currency      = $request->currency ?? 'USD';
        $returnParams  = $request->return_params ?? '';
        
        // Fields from official documentation
        $firstName       = $request->first_name ?? 'ABA';
        $lastName        = $request->last_name ?? 'Bank';
        $email           = $request->email ?? '';
        $phone           = $request->phone ?? '';
        $purchaseType    = $request->purchase_type ?? 'purchase';
        $callbackUrl     = $request->callback_url ?? '';
        $returnDeeplink  = $request->return_deeplink ?? '';
        $customFields    = $request->custom_fields ?? '';
        $payout          = $request->payout ?? '';
        $lifetime        = 45; // Minutes
        $qrImageTemplate = 'template3_color';

        // Revised Hashing Algorithm (18-param order based on successful session log)
        $hashData = [
            $reqTime,
            $this->merchantId,
            $tranId,
            $amount,
            $itemsBase64,
            $firstName,
            $lastName,
            $email,
            $phone,
            $purchaseType,
            $paymentOption,
            $callbackUrl,
            $returnDeeplink,
            $currency,
            $customFields,
            $returnParams,
            (string) $lifetime,
            (string) $qrImageTemplate,
        ];

        $hashString = implode('', $hashData);
        Log::info('PayWay Official Hash String: ' . json_encode(['string' => $hashString]));

        $hash = $this->sign($hashData);

        $payload = [
            'req_time'          => (string) $reqTime,
            'merchant_id'       => (string) $this->merchantId,
            'tran_id'           => (string) $tranId,
            'first_name'        => (string) $firstName,
            'last_name'         => (string) $lastName,
            'email'             => (string) $email,
            'phone'             => (string) $phone,
            'amount'            => $amount,
            'purchase_type'     => (string) $purchaseType,
            'payment_option'    => (string) $paymentOption,
            'items'             => (string) $itemsBase64,
            'currency'          => (string) $currency,
            'callback_url'      => (string) $callbackUrl,
            'return_params'     => $returnParams ? (string) $returnParams : null,
            'return_deeplink'   => $returnDeeplink ? (string) $returnDeeplink : null,
            'custom_fields'     => $customFields ? (string) $customFields : null,
            'payout'            => $payout ? (string) $payout : null,
            'lifetime'          => (int) $lifetime,
            'qr_image_template' => (string) $qrImageTemplate,
            'hash'              => (string) $hash,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json'
                ])
                ->post("{$this->baseUrl}/api/payment-gateway/v1/payments/generate-qr", $payload);

            $apiResponse = $response->json();
            
            if ($response->successful() && isset($apiResponse['status']) && $apiResponse['status']['code'] == '0') {
                $qrString = $apiResponse['qr_string'] ?? '';
                $qrImage  = $apiResponse['qr_image'] ?? '';
                
                // Robust approach: always generate a high-quality local QR from the string
                $qrStringForLocal = $qrString;
                if (empty($qrStringForLocal)) {
                    $deeplink = $apiResponse['abapay_deeplink'] ?? '';
                    if (!empty($deeplink)) {
                        $query = parse_url($deeplink, PHP_URL_QUERY);
                        parse_str($query, $queryParams);
                        $qrStringForLocal = $queryParams['qrcode'] ?? '';
                    }
                }

                $localQr = null;
                if (!empty($qrStringForLocal)) {
                    try {
                        // Try PNG first (as requested/seen in sample)
                        $localQr = 'data:image/png;base64,' . base64_encode(
                            QrCode::format('png')->size(500)->margin(3)->generate($qrStringForLocal)
                        );
                    } catch (\Exception $e) {
                        Log::warning('Local PNG QR failed, using SVG: ' . $e->getMessage());
                        try {
                            // High-contrast, large-margin SVG
                            $localQr = 'data:image/svg+xml;base64,' . base64_encode(
                                QrCode::format('svg')
                                    ->size(700)
                                    ->margin(5)
                                    ->color(0, 0, 0)
                                    ->backgroundColor(255, 255, 255)
                                    ->generate($qrStringForLocal)
                            );
                        } catch (\Exception $e2) {
                            Log::error('Local SVG QR failed: ' . $e2->getMessage());
                        }
                    }
                }

                $finalQrOutput = (!empty($qrImage) ? 'data:image/png;base64,' . $qrImage : '') ?: $localQr;

                $result = [
                    'qrString'         => $qrString ?: ($qrStringForLocal ?? ''),
                    'qrImage'          => $finalQrOutput,
                    'abapay_deeplink'  => $apiResponse['abapay_deeplink'] ?? '',
                    'app_store'        => 'https://apps.apple.com/kh/app/aba-payway-sandbox/id1534377543',
                    'play_store'       => 'https://play.google.com/store/apps/details?id=kh.com.aba.payway.sandbox',
                    'amount'           => (float) $amount,
                    'currency'         => $currency,
                    'status'           => $apiResponse['status'] ?? [
                        'code' => '0',
                        'message' => 'Success.',
                        'tran_id' => $tranId,
                        'lang' => 'en',
                        'trace_id' => bin2hex(random_bytes(16))
                    ],
                    'request'          => $payload,
                    'success'          => true,
                ];
            } else {
                Log::error('PayWay Generate QR Failed' , [
                    'status'   => $response->status(),
                    'payload'  => $payload,
                    'response' => $apiResponse ?? $response->body()
                ]);

                $result = [
                    'success'     => false,
                    'endpoint'    => 'Generate QR',
                    'status_code' => $response->status(),
                    'request'     => $payload,
                    'response'    => $apiResponse ?? $response->body(),
                ];
            }

            Log::info('PayWay Generate QR Final Response', $result);

        } catch (\Exception $e) {
            $result = [
                'success'  => false,
                'endpoint' => 'Generate QR',
                'response' => ['error' => $e->getMessage()]
            ];
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function transactionDetail(Request $request)
    {
        Log::info('PayWay Transaction Detail Request Hit', ['tran_id' => $request->tran_id]);
        $request->validate(['tran_id' => 'required|string']);
        
        $reqTime = $this->reqTime();
        $tranId  = $request->tran_id;

        $hash = $this->sign([
            $this->merchantId,
            $reqTime,
            $tranId,
        ]);

        $payload = [
            'req_time'    => $reqTime,
            'merchant_id' => $this->merchantId,
            'tran_id'     => $tranId,
            'hash'        => $hash,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post("{$this->baseUrl}/api/payment-gateway/v1/payments/transaction-detail", $payload);

            $result = [
                'endpoint'    => 'Transaction Detail',
                'status_code' => $response->status(),
                'request'     => $payload,
                'response'    => $response->json() ?? $response->body(),
                'success'     => $response->successful(),
            ];
            Log::info('PayWay Transaction Detail', $result);
        } catch (\Exception $e) {
            $result = ['endpoint' => 'Transaction Detail', 'success' => false, 'response' => ['error' => $e->getMessage()]];
        }
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function checkTransaction(Request $request)
    {
        Log::info('PayWay Check Transaction Request Hit', ['tran_id' => $request->tran_id]);
        $request->validate(['tran_id' => 'required|string']);
        
        $reqTime = $this->reqTime();
        $tranId  = $request->tran_id;

        // V1 Check Transaction Hashing: merchant_id + req_time + tran_id
        $hash = $this->sign([
            $this->merchantId,
            $reqTime,
            $tranId,
        ]);

        $payload = [
            'req_time'    => $reqTime,
            'merchant_id' => $this->merchantId,
            'tran_id'     => $tranId,
            'hash'        => $hash,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post("{$this->baseUrl}/api/payment-gateway/v1/payments/check-transaction", $payload);

            $result = [
                'endpoint'    => 'Check Transaction',
                'status_code' => $response->status(),
                'request'     => $payload,
                'response'    => $response->json() ?? $response->body(),
                'success'     => $response->successful(),
            ];
            Log::info('PayWay Check Transaction', $result);
        } catch (\Exception $e) {
            $result = ['endpoint' => 'Check Transaction', 'success' => false, 'response' => ['error' => $e->getMessage()]];
        }
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function refund(Request $request)
    {
        $request->validate([
            'tran_id'        => 'required|string',
            'refund_amount'  => 'required|numeric|min:0.01',
            'refund_reason'  => 'nullable|string|max:255',
        ]);

        $reqTime      = $this->reqTime();
        $tranId       = $request->tran_id;
        $refundAmount = number_format((float) $request->refund_amount, 2, '.', '');
        $refundReason = $request->refund_reason ?? 'Customer request';

        $hash = $this->sign([
            $this->merchantId,
            $reqTime,
            $tranId,
            $refundAmount,
        ]);

        $payload = [
            'req_time'      => $reqTime,
            'merchant_id'   => $this->merchantId,
            'tran_id'       => $tranId,
            'refund_amount' => $refundAmount,
            'refund_reason' => $refundReason,
            'hash'          => $hash,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post("{$this->baseUrl}/api/merchant-portal/merchant-access/online-transaction/refund", $payload);

            $result = [
                'endpoint'    => 'Refund',
                'status_code' => $response->status(),
                'request'     => $payload,
                'response'    => $response->json() ?? $response->body(),
                'success'     => $response->successful(),
            ];
            Log::info('PayWay Refund', $result);
        } catch (\Exception $e) {
            $result = ['endpoint' => 'Refund', 'success' => false, 'response' => ['error' => $e->getMessage()]];
        }
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function index()
    {
        return view('aba_payway');
    }
}
