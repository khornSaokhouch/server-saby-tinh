<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayoutController extends Controller
{
    /**
     * Display a listing of payouts.
     */
    public function index(Request $request)
    {
        $query = Payout::with(['invoice', 'store', 'status']);

        // Security override: Owners can only see their own store's payouts
        if (auth()->check() && auth()->user()->role === 'owner') {
            $ownerStore = \App\Models\Store::where('user_id', auth()->id())->first();
            if ($ownerStore) {
                $query->where('store_id', $ownerStore->id);
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

        return response()->json([
            'success' => true,
            'message' => 'Payout record created successfully',
            'data' => $payout->load(['invoice', 'store', 'status'])
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

        $created = [];
        $failed  = [];
        $now     = now();
        $batchRef = 'BULK-' . $now->timestamp;

        DB::transaction(function () use ($request, $now, $batchRef, &$created, &$failed) {
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
                    $created[] = $payout->id;
                } catch (\Exception $e) {
                    $failed[] = $item['invoice_id'];
                }
            }
        });

        return response()->json([
            'success'       => true,
            'total'         => count($request->payouts),
            'created_count' => count($created),
            'failed_count'  => count($failed),
            'failed_ids'    => $failed,
        ], 201);
    }
}
