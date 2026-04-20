<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\PayoutNotificationMail;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PayoutController extends Controller
{
    /**
     * Display a listing of payouts.
     */
    public function index(Request $request)
    {
        $query = Payout::with(['invoice', 'store', 'status']);

        // Security override: Non-admins can only see their own store's payouts
        $user = auth()->user();
        if ($user && $user->role !== 'admin') {
            $store = $user->accessible_store;
            if ($store) {
                $query->where('store_id', $store->id);
            } else {
                return response()->json(['success' => true, 'data' => []]);
            }
        } elseif ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('payment_status_id')) {
            $query->where('payment_status_id', $request->payment_status_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate($request->get('per_page', 15))
        ]);
    }

    /**
     * Store a newly created payout in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|in:KHR,USD',
            'payment_status_id' => 'required|exists:payment_statuses,id',
            'paid_at' => 'nullable|date',
            'transaction_reference' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $payout = Payout::create($request->all());
        $payout->load(['invoice', 'store.user', 'status']);

        // Send ONE email with a single-item collection
        try {
            $storeOwnerEmail = $payout->store?->user?->email;
            if ($storeOwnerEmail) {
                Mail::to($storeOwnerEmail)->send(new PayoutNotificationMail(collect([$payout])));
            }
        } catch (\Exception $e) {
            Log::error('Payout email failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Payout record created successfully',
            'data'    => $payout
        ], 201);
    }

    /**
     * Display the specified payout.
     */
    public function show(Payout $payout)
    {
        return response()->json([
            'success' => true,
            'data' => $payout->load(['invoice', 'store', 'status'])
        ]);
    }

    /**
     * Update the specified payout in storage.
     */
    public function update(Request $request, Payout $payout)
    {
        $validator = Validator::make($request->all(), [
            'payment_status_id' => 'sometimes|exists:payment_statuses,id',
            'paid_at' => 'nullable|date',
            'transaction_reference' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $payout->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Payout record updated successfully',
            'data' => $payout->load(['invoice', 'store', 'status'])
        ]);
    }

    /**
     * Remove the specified payout from storage.
     */
    public function destroy(Payout $payout)
    {
        $payout->delete();
        return response()->json([
            'success' => true,
            'message' => 'Payout record deleted successfully'
        ]);
    }

    /**
     * Bulk-create payout records in a single transaction.
     * Request body: { store_id, currency, payment_status_id, payouts: [{ invoice_id, amount }] }
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id'           => 'required|exists:stores,id',
            'currency'           => 'required|string|in:KHR,USD',
            'payment_status_id'  => 'required|exists:payment_statuses,id',
            'payouts'            => 'required|array|min:1',
            'payouts.*.invoice_id' => 'required|exists:invoices,id',
            'payouts.*.amount'     => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $created     = [];
        $failed      = [];
        $createdModels = [];
        $now         = now();
        $batchRef    = 'BULK-' . $now->timestamp;

        DB::transaction(function () use ($request, $now, $batchRef, &$created, &$failed, &$createdModels) {
            foreach ($request->payouts as $item) {
                try {
                    $payout = Payout::create([
                        'invoice_id'            => $item['invoice_id'],
                        'store_id'              => $request->store_id,
                        'amount'                => $item['amount'],
                        'currency'              => $request->currency,
                        'payment_status_id'     => $request->payment_status_id,
                        'paid_at'               => $now,
                        'transaction_reference' => $batchRef . '-' . $item['invoice_id'],
                    ]);
                    $payout->load(['invoice', 'store.user', 'status']);
                    $created[]       = $payout->id;
                    $createdModels[] = $payout;
                } catch (\Exception $e) {
                    $failed[] = $item['invoice_id'];
                }
            }
        });

        // Send ONE summary email for ALL payouts in this batch
        try {
            $payoutCollection = collect($createdModels);
            if ($payoutCollection->isNotEmpty()) {
                $storeOwnerEmail = $payoutCollection->first()?->store?->user?->email;
                if ($storeOwnerEmail) {
                    Mail::to($storeOwnerEmail)->send(new PayoutNotificationMail($payoutCollection));
                }
            }
        } catch (\Exception $mailEx) {
            Log::error('Bulk payout summary email failed: ' . $mailEx->getMessage());
        }

        return response()->json([
            'success'       => true,
            'total'         => count($request->payouts),
            'created_count' => count($created),
            'failed_count'  => count($failed),
            'failed_ids'    => $failed,
        ], 201);
    }
}
