<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ShopOrder;
use App\Models\OrderLine;
use App\Models\Store;
use App\Models\Payout;

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

    /**
     * Get top customers for a store based on total spent
     */
    public function topCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $storeId = $request->input('store_id');
        $limit = $request->input('limit', 5);

        $topCustomers = DB::table('shop_orders')
            ->join('users', 'shop_orders.user_id', '=', 'users.id')
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('order_lines', 'shop_orders.id', '=', 'order_lines.order_id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'user_profiles.profile_image as profile_image',
                DB::raw('COUNT(DISTINCT shop_orders.id) as total_orders'),
                DB::raw('SUM(order_lines.quantity * order_lines.price) as total_spent')
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'user_profiles.profile_image')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topCustomers
        ]);
    }

    /**
     * Comprehensive analytics for a store.
     */
    public function analytics(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'range' => 'nullable|string|in:7days,30days,thisMonth,thisYear'
        ]);

        $storeId = $request->input('store_id');
        $range = $request->input('range', 'thisYear');
        $year = date('Y');

        // --- 1. ALWAYS GET MONTHLY BUCKETS FOR THE CHART (CURRENT YEAR) ---
        $monthlyData = OrderLine::join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->whereYear('shop_orders.order_date', $year)
            ->select(
                DB::raw('MONTH(shop_orders.order_date) as month'),
                DB::raw('SUM(order_lines.price * order_lines.quantity) as revenue'),
                DB::raw('COUNT(DISTINCT shop_orders.id) as orders')
            )
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $revenueBuckets = [];
        $orderBuckets = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenueBuckets[] = (float) ($monthlyData->get($m)->revenue ?? 0);
            $orderBuckets[] = (int) ($monthlyData->get($m)->orders ?? 0);
        }

        // --- 2. CALCULATE KPIs & BREAKDOWNS BASED ON RANGE ---
        $dateQuery = function($q) use ($range) {
            if ($range === '7days') return $q->where('shop_orders.order_date', '>=', now()->subDays(7));
            if ($range === '30days') return $q->where('shop_orders.order_date', '>=', now()->subDays(30));
            if ($range === 'thisMonth') return $q->whereMonth('shop_orders.order_date', now()->month)->whereYear('shop_orders.order_date', now()->year);
            return $q->whereYear('shop_orders.order_date', now()->year);
        };

        // Status Breakdown
        $statusBreakdown = ShopOrder::join('order_statuses', 'shop_orders.order_status_id', '=', 'order_statuses.id')
            ->whereHas('orderLines.productItemVariant.productItem.product', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->when($range, $dateQuery)
            ->select('order_statuses.status as label', DB::raw('COUNT(*) as value'))
            ->groupBy('order_statuses.status')
            ->get();

        // Payment Health
        $paymentBreakdown = ShopOrder::join('payment_statuses', 'shop_orders.payment_status_id', '=', 'payment_statuses.id')
            ->whereHas('orderLines.productItemVariant.productItem.product', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->when($range, $dateQuery)
            ->select('payment_statuses.status as label', DB::raw('COUNT(*) as value'))
            ->groupBy('payment_statuses.status')
            ->get();

        // Category Breakdown
        $categoryBreakdown = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('product_items', 'products.id', '=', 'product_items.product_id')
            ->join('product_item_variants', 'product_items.id', '=', 'product_item_variants.product_item_id')
            ->join('order_lines', 'product_item_variants.id', '=', 'order_lines.product_item_variant_id')
            ->join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->where('products.store_id', $storeId)
            ->when($range, $dateQuery)
            ->select('categories.name as label', DB::raw('COUNT(DISTINCT products.id) as value'))
            ->groupBy('categories.name')
            ->orderByDesc('value')
            ->limit(5)
            ->get();

        // KPIs
        $kpiData = OrderLine::join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->when($range, $dateQuery)
            ->select(
                DB::raw('SUM(order_lines.price * order_lines.quantity) as revenue'),
                DB::raw('COUNT(DISTINCT shop_orders.id) as orders'),
                DB::raw('COUNT(DISTINCT CASE WHEN shop_orders.payment_status_id = 2 THEN shop_orders.id END) as paid'),
                DB::raw('COUNT(DISTINCT CASE WHEN shop_orders.order_status_id = 1 THEN shop_orders.id END) as pending')
            )
            ->first();

        // Top Products for the period
        $topProducts = DB::table('products')
            ->join('product_items', 'products.id', '=', 'product_items.product_id')
            ->join('product_item_variants', 'product_items.id', '=', 'product_item_variants.product_item_id')
            ->join('order_lines', 'product_item_variants.id', '=', 'order_lines.product_item_variant_id')
            ->join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->leftJoin('product_images', function($q) {
                $q->on('products.id', '=', 'product_images.product_id')->where('product_images.is_primary', 1);
            })
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.store_id', $storeId)
            ->when($range, $dateQuery)
            ->select(
                'products.id',
                'products.name',
                'categories.name as category',
                'product_images.image as image',
                DB::raw('SUM(order_lines.quantity) as units'),
                DB::raw('SUM(order_lines.price * order_lines.quantity) as revenue')
            )
            ->groupBy('products.id', 'products.name', 'categories.name', 'product_images.image')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'revenue' => (float) ($kpiData->revenue ?? 0),
                    'orders' => (int) ($kpiData->orders ?? 0),
                    'avg' => $kpiData->orders > 0 ? ($kpiData->revenue / $kpiData->orders) : 0,
                    'paid' => (int) ($kpiData->paid ?? 0),
                    'pending' => (int) ($kpiData->pending ?? 0),
                ],
                'monthly_revenue' => $revenueBuckets,
                'monthly_orders' => $orderBuckets,
                'status_breakdown' => $statusBreakdown,
                'payment_breakdown' => $paymentBreakdown,
                'category_breakdown' => $categoryBreakdown,
                'top_products' => $topProducts
            ]
        ]);
    }

    /**
     * Store Owner Dashboard Pulse.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $request->validate(['store_id' => 'required|exists:stores,id']);
        $storeId = $request->input('store_id');

        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $startOfWeek = now()->startOfWeek();

        $allTimeRevenue = OrderLine::join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->sum(DB::raw('order_lines.price * order_lines.quantity'));

        $todayRevenue = OrderLine::join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->where('shop_orders.order_date', '>=', $today)
            ->sum(DB::raw('order_lines.price * order_lines.quantity'));

        $yesterdayRevenue = OrderLine::join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->where('shop_orders.order_date', '>=', $yesterday)
            ->where('shop_orders.order_date', '<', $today)
            ->sum(DB::raw('order_lines.price * order_lines.quantity'));

        $weekRevenue = OrderLine::join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->where('shop_orders.order_date', '>=', $startOfWeek)
            ->sum(DB::raw('order_lines.price * order_lines.quantity'));

        $pendingOrders = ShopOrder::where('order_status_id', 1) // 1 = Pending
            ->whereHas('orderLines.productItemVariant.productItem.product', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })->count();

        $lowStock = DB::table('product_item_variants')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->where('product_item_variants.quantity_in_stock', '<=', 5)
            ->count();

        // 2. Chart: Last 30 Days Trend
        $last30Days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $last30Days->put($date, ['date' => $date, 'revenue' => 0, 'orders' => 0]);
        }

        $trendData = OrderLine::join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->where('shop_orders.order_date', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(shop_orders.order_date) as date'),
                DB::raw('SUM(order_lines.price * order_lines.quantity) as revenue'),
                DB::raw('COUNT(DISTINCT shop_orders.id) as orders')
            )
            ->groupBy('date')
            ->get();

        foreach ($trendData as $row) {
            if ($last30Days->has($row->date)) {
                $last30Days->put($row->date, [
                    'date' => $row->date,
                    'revenue' => (float)$row->revenue,
                    'orders' => (int)$row->orders
                ]);
            }
        }

        // 3. Recent Activity (Orders)
        $recentOrders = ShopOrder::with(['orderStatus', 'paymentStatus', 'user'])
            ->whereHas('orderLines.productItemVariant.productItem.product', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->latest('order_date')
            ->limit(5)
            ->get();

        // 4. Recent Activity (Payouts)
        $recentPayouts = Payout::where('store_id', $storeId)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'pulse' => [
                    'all_time_revenue' => (float)$allTimeRevenue,
                    'today_revenue' => (float)$todayRevenue,
                    'yesterday_revenue' => (float)$yesterdayRevenue,
                    'week_revenue' => (float)$weekRevenue,
                    'pending_orders' => $pendingOrders,
                    'low_stock' => $lowStock,
                ],
                'trend' => $last30Days->values(),
                'recent_orders' => $recentOrders,
                'recent_payouts' => $recentPayouts
            ]
        ]);
    }
}
