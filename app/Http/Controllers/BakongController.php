<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ShopOrder;
use App\Models\Store;
use App\Models\UserPayment;
use App\Models\PaymentStatus;
use App\Models\PaymentAccount;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class BakongController extends Controller
{
    /**
     * Generate a Bakong QR code (API)
     */
    public function generateQr(Request $request)
    {
        $request->validate([
            'order_id'           => 'required|exists:shop_orders,id',
            'currency'           => 'required|in:KHR,USD',
            'payment_account_id' => 'required|exists:payment_accounts,id'
        ]);

        $order = ShopOrder::findOrFail($request->order_id);
        $currency = $request->input('currency', 'KHR');
        $paymentAccount = PaymentAccount::findOrFail($request->payment_account_id);

        // For demo purposes, we're using order_total. 
        // If order_total is in USD and we need KHR, we'd convert it.
        $displayAmount = ($currency === 'USD') ? round($order->order_total, 2) : round($order->order_total * 4100, 0);
        $khqrCurrency = ($currency === 'USD') ? KHQRData::CURRENCY_USD : KHQRData::CURRENCY_KHR;

        try {
            // Generate KHQR string manually to match node.js correct standard
            $khqrString = $this->generateKhqrString(
                $paymentAccount->account_id,
                $paymentAccount->account_name,
                $paymentAccount->account_city ?? 'Phnom Penh',
                $displayAmount,
                $currency,
                'INV-' . $order->id
            );
            $md5 = md5($khqrString);


            // Get stylized QR image from Relay API
            $qrImage = null;
            try {
                $relayUrl = env('API_GENERATE_QR_BAKONG', 'https://api.bakongrelay.com/v1/generate_khqr_image');
                $templateUrl = env('QR_TEMPLATE_URL', 'https://raw.githubusercontent.com/bsthen/bakong-khqr/main/bakong_khqr/template.png');

                $relayResponse = \Illuminate\Support\Facades\Http::timeout(10)->post($relayUrl, [
                    'qr' => $khqrString,
                    'source' => $templateUrl
                ]);

                if ($relayResponse->successful()) {
                    $rawBody = $relayResponse->body();
                    $isJson = false;

                    if (str_starts_with(trim($rawBody), '{')) {
                        $json = json_decode($rawBody, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $isJson = true;
                            $imageData = $json['data'] ?? $json['qr_image'] ?? $json['image'] ?? null;
                            if (is_array($imageData)) {
                                $imageData = $imageData['image'] ?? $imageData['base64'] ?? $imageData['url'] ?? $imageData['data'] ?? null;
                            }
                            if (is_string($imageData) && !str_starts_with(trim($imageData), '{')) {
                                $qrImage = str_starts_with($imageData, 'data:image') ? $imageData : 'data:image/png;base64,' . $imageData;
                            }
                        }
                    }

                    if (!$isJson && !$qrImage) {
                        $qrImage = 'data:image/png;base64,' . base64_encode($rawBody);
                    }
                }
            } catch (\Exception $e) {
                Log::error('[KHQR] Relay API failed: ' . $e->getMessage());
            }

            // Save the payment record
            UserPayment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'user_id'            => auth()->id() ?? $order->user_id,
                    'payment_method_id'  => $paymentAccount->id,
                    'payment_status_id'  => 1, // Pending
                    'transaction_id'     => $md5, // Use MD5 as temporary transaction ID
                    'amount'             => $displayAmount,
                    'currency'           => $currency,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_string'       => $khqrString,
                    'qr_image'        => $qrImage,
                    'md5'             => $md5,
                    'amount'          => $displayAmount,
                    'currency'        => $currency,
                    'order_id'        => $order->id,
                    'original_total'  => $order->subtotal + $order->shipping_fee,
                    'discount_amount' => $order->discount_amount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a Bakong QR code for Store Payout
     */
    public function generatePayoutQr(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount'   => 'required|numeric|min:0.01',
            'currency' => 'required|in:KHR,USD'
        ]);

        $store = Store::findOrFail($request->store_id);
        $currency = $request->input('currency', 'USD');
        
        // Find a payment account for the store owner. For simplicity, we just grab the first one.
        $paymentAccount = PaymentAccount::where('user_id', $store->user_id)->first();

        if (!$paymentAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Store owner has no registered Payment Account.'
            ], 422);
        }

        // Override currency with the account's configured currency if available
        if (!empty($paymentAccount->currency)) {
            $currency = $paymentAccount->currency;
        }

        // For demo purposes, we're using amount. 
        // If amount is in USD and we need KHR, we'd convert it.
        $displayAmount = ($currency === 'USD') ? round($request->amount, 2) : round($request->amount * 4100, 0);
        $khqrCurrency = ($currency === 'USD') ? KHQRData::CURRENCY_USD : KHQRData::CURRENCY_KHR;

        try {
            $khqrString = $this->generateKhqrString(
                $paymentAccount->account_id,
                $paymentAccount->account_name,
                $paymentAccount->account_city ?? 'Phnom Penh',
                $displayAmount,
                $currency,
                'PAYOUT-' . time()
            );

            $qrImage = null;
            try {
                $relayUrl = env('API_GENERATE_QR_BAKONG', 'https://api.bakongrelay.com/v1/generate_khqr_image');
                $templateUrl = env('QR_TEMPLATE_URL', 'https://raw.githubusercontent.com/bsthen/bakong-khqr/main/bakong_khqr/template.png');

                $relayResponse = \Illuminate\Support\Facades\Http::timeout(10)->post($relayUrl, [
                    'qr' => $khqrString,
                    'source' => $templateUrl
                ]);

                if ($relayResponse->successful()) {
                    $rawBody = $relayResponse->body();
                    $isJson = false;

                    if (str_starts_with(trim($rawBody), '{')) {
                        $json = json_decode($rawBody, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $isJson = true;
                            $imageData = $json['data'] ?? $json['qr_image'] ?? $json['image'] ?? null;
                            if (is_array($imageData)) {
                                $imageData = $imageData['image'] ?? $imageData['base64'] ?? $imageData['url'] ?? $imageData['data'] ?? null;
                            }
                            if (is_string($imageData) && !str_starts_with(trim($imageData), '{')) {
                                $qrImage = str_starts_with($imageData, 'data:image') ? $imageData : 'data:image/png;base64,' . $imageData;
                            }
                        }
                    }

                    if (!$isJson && !$qrImage) {
                        $qrImage = 'data:image/png;base64,' . base64_encode($rawBody);
                    }
                }
            } catch (\Exception $e) {
                Log::error('[KHQR] Relay API failed for payout: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_string'       => $khqrString,
                    'qr_image'        => $qrImage,
                    'amount'          => $displayAmount,
                    'currency'        => $currency,
                    'account_name'    => $paymentAccount->account_name,
                    'account_id'      => $paymentAccount->account_id,
                    'md5'             => md5($khqrString)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check Bakong transaction by MD5 (API)
     */
    public function checkMd5(Request $request)
    {
        $request->validate(['md5' => 'required|string']);

        try {
            $bakongKhqr = new BakongKHQR(env('BAKONG_API_TOKEN'));
            $response = $bakongKhqr->checkTransactionByMD5($request->md5);

            Log::info('Bakong MD5 Check Response', ['md5' => $request->md5, 'response' => $response]);

            $responseCode = $response['responseCode'] ?? ($response['status']['code'] ?? null);
            $isSuccess = ($responseCode === 0 || $responseCode === '0' || $responseCode === '00');

            if ($isSuccess) {
                $userPayment = UserPayment::where('transaction_id', $request->md5)->first();
                if ($userPayment) {
                    $userPayment->update([
                        'payment_status_id' => 2, // Success
                        'paid_at'           => now(),
                    ]);

                    // Also update the order
                    $order = ShopOrder::find($userPayment->order_id);
                    if ($order) {
                        $order->update(['payment_status_id' => 2]);
                        
                        // NEW Logic: Record promo usage and update invoice
                        $order->recordPromoUsage();
                        $order->updateInvoiceStatus();
                    }
                }
            }

            return response()->json([
                'success' => $isSuccess,
                'status'  => $isSuccess ? 'success' : 'failed',
                'raw_response' => $response['data'] ?? $response,
                'message' => $response['responseMessage'] ?? ($response['status']['message'] ?? '')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to compute CRC16 CCITT
     */
    private function calculateCrc16($data)
    {
        $crc = 0xFFFF;
        $jf = 0x1021;
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $b = ord($data[$i]);
            for ($j = 0; $j < 8; $j++) {
                $bit = (($b >> (7 - $j)) & 1) == 1;
                $c15 = (($crc >> 15) & 1) == 1;
                $crc <<= 1;
                if ($c15 ^ $bit) {
                    $crc ^= $jf;
                }
            }
        }
        $crc &= 0xFFFF;
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Helper to compute TLV
     */
    private function generateTlv($tag, $value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        $valueStr = (string) $value;
        $length = str_pad((string) strlen($valueStr), 2, '0', STR_PAD_LEFT);
        return $tag . $length . $valueStr;
    }

    /**
     * Generate KHQR directly matching validated node.js structure
     */
    private function generateKhqrString($bankAccount, $merchantName, $merchantCity, $amount, $currency, $billNumber)
    {
        $qr = "";
        $qr .= $this->generateTlv("00", "01"); // Payload Format Indicator
        $qr .= $this->generateTlv("01", "12"); // Point of Initiation (Dynamic)
        
        // Tag 29: Individual Bakong Account
        $qr .= $this->generateTlv("29", $this->generateTlv("00", $bankAccount));
        
        $qr .= $this->generateTlv("52", "5999"); // MCC
        $qr .= $this->generateTlv("53", $currency === 'KHR' ? "116" : "840"); // Transaction Currency
        
        if ($amount !== null && $amount !== '') {
            if ($currency === 'KHR') {
                $amountStr = (string) round((float)$amount);
            } else {
                $amountStr = number_format((float)$amount, 2, '.', '');
            }
            $qr .= $this->generateTlv("54", $amountStr);
        }
        
        $qr .= $this->generateTlv("58", "KH");
        $qr .= $this->generateTlv("59", $merchantName);
        $qr .= $this->generateTlv("60", $merchantCity ?: "Phnom Penh");
        
        // Tag 99: Timestamp (identical to Node.js / Python)
        $now = (int) round(microtime(true) * 1000);
        $expiry = $now + (86400000 * 1); // 1 day
        $tag99inner = $this->generateTlv("00", (string)$now) . $this->generateTlv("01", (string)$expiry);
        $qr .= $this->generateTlv("99", $tag99inner);
        
        if ($billNumber) {
            $qr .= $this->generateTlv("62", $this->generateTlv("01", $billNumber));
        }
        
        $qr .= "6304";
        $qr .= $this->calculateCrc16($qr);
        
        return $qr;
    }
}