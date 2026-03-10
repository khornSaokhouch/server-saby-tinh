<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ShopOrder;
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
            $individualInfo = new IndividualInfo(
                bakongAccountID: $paymentAccount->account_id,
                merchantName: $paymentAccount->account_name,
                merchantCity: $paymentAccount->account_city ?? 'PHNOM PENH',
                currency: $khqrCurrency,
                amount: (float) $displayAmount,
                billNumber: 'INV-' . strtoupper(uniqid()),
            );

            $response = BakongKHQR::generateIndividual($individualInfo);

            if ($response->status['code'] !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => $response->status['message']
                ], 400);
            }

            $khqrString = $response->data['qr'];
            $md5 = $response->data['md5'];

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
}