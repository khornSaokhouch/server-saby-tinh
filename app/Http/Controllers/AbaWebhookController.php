<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AbaWebhookController extends Controller
{
    /**
     * Handle PayWay Webhook notification.
     * 
     * PayWay will POST to this URL when a payment is successful.
     */
    public function handle(Request $request)
    {
        Log::info('PayWay Webhook Hit:', $request->all());

        // According to KHQR Guideline, we receive:
        // transaction_id, transaction_date, original_currency, original_amount,
        // bank_ref, apv, payment_status_code, payment_status, payment_currency, 
        // payment_amount, payment_type, payer_account, bank_name, merchant_ref

        $data = $request->all();

        if (empty($data)) {
            Log::warning('PayWay Webhook: Empty payload received.');
            return response()->json(['message' => 'Empty payload'], 400);
        }

        // 0 represents success according to the guide
        if (isset($data['payment_status_code']) && $data['payment_status_code'] == 0) {
            Log::info('PayWay Payment Approved:', [
                'tran_id' => $data['transaction_id'] ?? 'N/A',
                'amount'  => $data['payment_amount'] ?? 'N/A',
                'currency' => $data['payment_currency'] ?? 'N/A',
                'bank_ref' => $data['bank_ref'] ?? 'N/A',
            ]);

            // Here you would typically:
            // 1. Find the order in your database using merchant_ref or transaction_id
            // 2. Update order status to 'paid'
            // 3. Trigger success actions (email, inventory, etc.)
            
            return response()->json(['message' => 'OK'], 200);
        }

        Log::warning('PayWay Webhook: Payment not successful or status unknown.', $data);
        return response()->json(['message' => 'OK'], 200); // Always return 200 to PayWay to prevent retries if handled
    }
}
