<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PromoCodeController extends Controller
{
    public function index(): JsonResponse
    {
        $promoCodes = PromoCode::latest()->get();
        return response()->json(['success' => true, 'data' => $promoCodes]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'                => 'required|string|unique:promo_codes',
            'description'         => 'nullable|string',
            'discount_type'       => 'required|string|in:percentage,fixed',
            'discount_value'      => 'required|numeric|min:0',
            'min_order_amount'    => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'         => 'nullable|integer|min:1',
            'per_user_limit'      => 'nullable|integer|min:1',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date',
            'status'              => 'nullable|boolean',
        ]);

        $promoCode = PromoCode::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Promo code created successfully',
            'data'    => $promoCode
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $promoCode = PromoCode::find($id);
        if (!$promoCode) return response()->json(['success' => false, 'message' => 'Promo code not found'], 404);

        $data = $request->validate([
            'code'                => 'sometimes|string|unique:promo_codes,code,' . $id,
            'description'         => 'nullable|string',
            'discount_type'       => 'sometimes|string|in:percentage,fixed',
            'discount_value'      => 'sometimes|numeric|min:0',
            'min_order_amount'    => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'         => 'nullable|integer|min:1',
            'per_user_limit'      => 'nullable|integer|min:1',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date',
            'status'              => 'nullable|boolean',
        ]);

        $promoCode->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Promo code updated successfully',
            'data'    => $promoCode
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $promoCode = PromoCode::find($id);
        if (!$promoCode) return response()->json(['success' => false, 'message' => 'Promo code not found'], 404);

        $promoCode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo code deleted successfully',
        ]);
    }

    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'order_total' => 'required|numeric'
        ]);

        // Find the code first to provide specific feedback (Case-insensitive)
        $promoCode = PromoCode::where('code', 'LIKE', $request->code)->first() ?? 
                     PromoCode::whereRaw('UPPER(code) = ?', [strtoupper($request->code)])->first();

        if (!$promoCode) {
            return response()->json(['success' => false, 'message' => 'Promo code "' . $request->code . '" not found'], 200);
        }

        if (!$promoCode->status) {
            return response()->json(['success' => false, 'message' => 'This promo code is currently inactive'], 200);
        }

        $now = now();
        if ($promoCode->start_date && $promoCode->start_date->gt($now)) {
            return response()->json([
                'success' => false, 
                'message' => 'This promo code is not yet active. Server time: ' . $now->format('Y-m-d H:i') . ', Starts: ' . $promoCode->start_date->format('Y-m-d H:i')
            ], 200);
        }

        if ($promoCode->end_date && $promoCode->end_date->lt($now)) {
            return response()->json([
                'success' => false, 
                'message' => 'This promo code expired on ' . $promoCode->end_date->format('Y-m-d H:i') . '. Server time: ' . $now->format('Y-m-d H:i')
            ], 200);
        }

        // Check usage limit
        if ($promoCode->usage_limit && $promoCode->usage_count >= $promoCode->usage_limit) {
            return response()->json(['success' => false, 'message' => 'Promo code usage limit reached'], 400);
        }

        // Check min order amount
        if ($promoCode->min_order_amount && $request->order_total < $promoCode->min_order_amount) {
            return response()->json(['success' => false, 'message' => 'Order total does not meet minimum requirement'], 400);
        }

        // Check per user limit (if authenticated)
        $user = Auth::user();
        if ($user && $promoCode->per_user_limit) {
            $usage = \App\Models\PromoCodeUsage::where('promo_code_id', $promoCode->id)
                ->where('user_id', $user->id)
                ->count();
            if ($usage >= $promoCode->per_user_limit) {
                return response()->json(['success' => false, 'message' => 'You have reached the usage limit for this code'], 400);
            }
        }

        // Calculate discount
        $discountAmount = 0;
        if ($promoCode->discount_type === 'percentage') {
            $discountAmount = ($request->order_total * $promoCode->discount_value) / 100;
            if ($promoCode->max_discount_amount && $discountAmount > $promoCode->max_discount_amount) {
                $discountAmount = $promoCode->max_discount_amount;
            }
        } else {
            $discountAmount = $promoCode->discount_value;
        }

        // Ensure discount doesn't exceed order total
        if ($discountAmount > $request->order_total) {
            $discountAmount = $request->order_total;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'promo_code' => $promoCode,
                'discount_amount' => $discountAmount
            ]
        ]);
    }
}
