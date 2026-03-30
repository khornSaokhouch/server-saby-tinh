<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ShopOrder;
use App\Models\OrderLine;
use App\Models\Store;

class ReportByStoreController extends Controller
{
    /**
     * Get aggregate stats for a store in a given date range.
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'range' => 'nullable|integer|in:7,15,30,180,365'
        ]);

        $storeId = $request->input('store_id');
        $days = $request->input('range', 7);

        $now = Carbon::now();
        $startDate = $now->copy()->subDays($days);
        $previousStartDate = $startDate->copy()->subDays($days);

        // Helper to query order lines for this store
        $queryLines = function ($start, $end) use ($storeId) {
            return OrderLine::join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
                ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
                ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
                ->join('products', 'product_items.product_id', '=', 'products.id')
                ->where('products.store_id', $storeId)
                ->whereBetween('shop_orders.order_date', [$start, $end]);
        };

        // Current Period
        $currentRevenue = (clone $queryLines($startDate, $now))->sum(DB::raw('order_lines.price * order_lines.quantity'));
        $currentOrders = (clone $queryLines($startDate, $now))->distinct('shop_orders.id')->count('shop_orders.id');
        $currentAov = $currentOrders > 0 ? ($currentRevenue / $currentOrders) : 0;

        // Previous Period
        $prevRevenue = (clone $queryLines($previousStartDate, $startDate))->sum(DB::raw('order_lines.price * order_lines.quantity'));
        $prevOrders = (clone $queryLines($previousStartDate, $startDate))->distinct('shop_orders.id')->count('shop_orders.id');
        $prevAov = $prevOrders > 0 ? ($prevRevenue / $prevOrders) : 0;

        // Calculate Growth (%)
        $revenueGrowth = $prevRevenue > 0 ? (($currentRevenue - $prevRevenue) / $prevRevenue) * 100 : ($currentRevenue > 0 ? 100 : 0);
        $ordersGrowth = $prevOrders > 0 ? (($currentOrders - $prevOrders) / $prevOrders) * 100 : ($currentOrders > 0 ? 100 : 0);
        $aovGrowth = $prevAov > 0 ? (($currentAov - $prevAov) / $prevAov) * 100 : ($currentAov > 0 ? 100 : 0);

        return response()->json([
            'success' => true,
            'data' => [
                'revenue' => round((float)$currentRevenue, 2),
                'revenueGrowth' => round((float)$revenueGrowth, 1),
                'orders' => $currentOrders,
                'ordersGrowth' => round((float)$ordersGrowth, 1),
                'avgOrderValue' => round((float)$currentAov, 2),
                'aovGrowth' => round((float)$aovGrowth, 1),
            ]
        ]);
    }

    /**
     * Get recent transactions (orders) for a store
     */
    public function recentOrders(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $storeId = $request->input('store_id');
        $limit = $request->input('limit', 5);

        $orders = ShopOrder::select(
                'shop_orders.id',
                'users.name as customer',
                'shop_orders.order_date as date',
                'order_statuses.status as status',
                'shop_orders.order_total as amount',
            )
            ->join('users', 'shop_orders.user_id', '=', 'users.id')
            ->join('order_statuses', 'shop_orders.order_status_id', '=', 'order_statuses.id')
            ->whereHas('orderLines.productItemVariant.productItem.product', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->withCount('orderLines as items')
            ->orderByDesc('shop_orders.order_date')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                // Map the dynamic status string to standard status we expect in UI if desired
                return [
                    'id' => 'ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT),
                    'customer' => $order->customer,
                    'date' => Carbon::parse($order->date)->format('Y-m-d H:i'),
                    'amount' => (float) $order->amount,
                    'status' => $order->status, // "Completed", "Processing", etc based on order_status table
                    'items' => $order->items
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get top selling products for a store
     */
    public function topProducts(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $storeId = $request->input('store_id');
        $limit = $request->input('limit', 4);

        $topProducts = DB::table('order_lines')
            ->join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'products.id',
                'products.name',
                'categories.name as category',
                DB::raw('SUM(order_lines.quantity) as sales'),
                DB::raw('SUM(order_lines.quantity * order_lines.price) as revenue')
            )
            ->where('products.store_id', $storeId)
            ->groupBy('products.id', 'products.name', 'categories.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        // Get inventory for these products (sum over variants)
        foreach ($topProducts as $product) {
            $product->inventory = DB::table('product_items')
                ->where('product_id', $product->id)
                ->sum('quantity_in_stock'); // Or get variants stock if that's what's tracked
            
            $product->sales = (int) $product->sales;
            $product->revenue = (float) $product->revenue;
        }

        return response()->json([
            'success' => true,
            'data' => $topProducts
        ]);
    }
}
