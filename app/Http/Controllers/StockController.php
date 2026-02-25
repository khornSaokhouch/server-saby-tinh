<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    /**
     * Display a listing of the stocks.
     * Filtered by authenticated owner's stores.
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Stock::with([
                'store',
                'productItem.product.images',
                'productItem.variants.color',
                'productItem.variants.size'
            ]);

            // Filter by owner
            if ($user->role === 'owner') {
                $query->whereHas('store', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $stocks = $query->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $stocks,
                'message' => 'Stocks retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stocks: ' . $e->getMessage()
            ], 500);
        }
    }
}
