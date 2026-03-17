<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search across products, stores, and categories.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([
                'success' => true,
                'data' => [
                    'products' => [],
                    'stores' => [],
                    'categories' => []
                ]
            ]);
        }

        // Search Products
        $products = Product::with(['images', 'store', 'category.promotions' => function ($q) {
                $q->where('promotions.status', 1);
                $now = now();
                $q->where(function ($sub) use ($now) {
                    $sub->whereNull('start_date')->orWhere('start_date', '<=', $now);
                })->where(function ($sub) use ($now) {
                    $sub->whereNull('end_date')->orWhere('end_date', '>=', $now);
                });
            }, 'items.variants'])
            ->addSelect(['*', 'reviews_avg_rating' => \App\Models\UserReview::selectRaw('avg(rating)')
                ->join('order_lines', 'user_reviews.order_line_id', '=', 'order_lines.id')
                ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
                ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
                ->whereColumn('product_items.product_id', 'products.id')
            ])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->where('status', 1) 
            ->limit(10)
            ->get();

        // Search Stores
        $stores = Store::where('name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();

        // Search Categories
        $categories = Category::where('name', 'LIKE', "%{$query}%")
            ->where('status', 1)
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'stores' => $stores,
                'categories' => $categories
            ]
        ]);
    }
}
