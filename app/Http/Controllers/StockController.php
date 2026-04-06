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

            // Filter to accessible store only
            if ($user->role !== 'admin') {
                $storeId = $user->accessible_store ? $user->accessible_store->id : null;
                if ($storeId) {
                    $query->where('store_id', $storeId);
                } else {
                    $query->whereNull('id'); // Return nothing if they have no store
                }
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
