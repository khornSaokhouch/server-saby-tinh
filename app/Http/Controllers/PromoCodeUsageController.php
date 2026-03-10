<?php

namespace App\Http\Controllers;

use App\Models\PromoCodeUsage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PromoCodeUsageController extends Controller
{
    /**
     * Display a listing of the promo code usages.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PromoCodeUsage::with(['promoCode', 'user', 'order'])
                ->latest();

            // Filtering
            if ($request->has('promo_code_id') && !empty($request->promo_code_id)) {
                $query->where('promo_code_id', $request->promo_code_id);
            }

            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                // Group the search filters to avoid interference with other where clauses
                $query->where(function($q) use ($search) {
                    $q->whereHas('promoCode', function($sub) use ($search) {
                        $sub->where('code', 'LIKE', "%{$search}%");
                    })->orWhereHas('user', function($sub) use ($search) {
                        $sub->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('email', 'LIKE', "%{$search}%");
                    });
                });
            }

            $usages = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $usages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Index error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get summary metrics for promo code usages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $totalUsages = PromoCodeUsage::count();
            $totalDiscount = PromoCodeUsage::sum('discount_amount') ?? 0;
            $uniqueUsers = PromoCodeUsage::distinct()->count('user_id');
            
            $topCode = PromoCodeUsage::select('promo_code_id')
                ->selectRaw('count(*) as usage_count')
                ->groupBy('promo_code_id')
                ->orderByDesc('usage_count')
                ->with('promoCode:id,code')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_usages' => $totalUsages,
                    'total_discount' => $totalDiscount,
                    'unique_users' => $uniqueUsers,
                    'top_code' => ($topCode && $topCode->promoCode) ? [
                        'code' => $topCode->promoCode->code,
                        'count' => (int) $topCode->usage_count
                    ] : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stats error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
