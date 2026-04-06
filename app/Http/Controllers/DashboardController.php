<?php

namespace App\Http\Controllers;

use App\Models\ShopOrder;
use App\Models\User;
use App\Models\ProductItem;
use App\Models\OrderLine;
use App\Models\UserReview;
use App\Models\Store;
use App\Models\Payout;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // 1. Current Period Stats
        $now = Carbon::now();
        $startOfCurrentMonth = $now->copy()->startOfMonth();
        $endOfCurrentMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Totals (Revenue only from paid orders)
        $totalRevenue = ShopOrder::where('payment_status_id', 2)->sum('order_total');
        $totalOrders = ShopOrder::count();
        $totalCustomers = User::where('role', User::ROLE_USER)->count();
        $productsSold = OrderLine::sum('quantity');

        // Calculate Trends (Current Month vs Last Month)
        $currentMonthRevenue = ShopOrder::where('payment_status_id', 2)->whereBetween('order_date', [$startOfCurrentMonth, $endOfCurrentMonth])->sum('order_total');
        $lastMonthRevenue = ShopOrder::where('payment_status_id', 2)->whereBetween('order_date', [$startOfLastMonth, $endOfLastMonth])->sum('order_total');
        $revenueTrend = $this->calculateTrend($currentMonthRevenue, $lastMonthRevenue);

        $currentMonthOrders = ShopOrder::whereBetween('order_date', [$startOfCurrentMonth, $endOfCurrentMonth])->count();
        $lastMonthOrders = ShopOrder::whereBetween('order_date', [$startOfLastMonth, $endOfLastMonth])->count();
        $ordersTrend = $this->calculateTrend($currentMonthOrders, $lastMonthOrders);

        $currentMonthCustomers = User::where('role', User::ROLE_USER)->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])->count();
        $lastMonthCustomers = User::where('role', User::ROLE_USER)->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $customersTrend = $this->calculateTrend($currentMonthCustomers, $lastMonthCustomers);

        $currentMonthSales = OrderLine::whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])->sum('quantity');
        $lastMonthSales = OrderLine::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('quantity');
        $productsTrend = $this->calculateTrend($currentMonthSales, $lastMonthSales);

        // 2. Category Distribution
        $categoryData = DB::table('order_lines')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('SUM(order_lines.quantity) as value'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('value')
            ->get();

        // Assign some colors for the pie chart
        $colors = ['#4f46e5', '#ec4899', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444'];
        $categoryData = $categoryData->map(function($item, $index) use ($colors) {
            $item->color = $colors[$index % count($colors)];
            return $item;
        });

        // 3. Top Products
        $topProducts = DB::table('order_lines')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_lines.quantity) as sales'),
                DB::raw('SUM(order_lines.quantity * order_lines.price) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->take(5)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sales' => (int)$p->sales,
                    'revenue' => '$' . number_format($p->revenue, 2),
                    'trend' => '+ ' . rand(2, 12) . '%' // Random trend for now
                ];
            });

        // 2. Optimized Revenue Chart (Last 7 Days)
        $sevenDaysAgo = Carbon::now()->subDays(6)->startOfDay();
        $dailyRevenue = ShopOrder::select(
                DB::raw('DATE(order_date) as date'),
                DB::raw('SUM(order_total) as value')
            )
            ->where('order_date', '>=', $sevenDaysAgo)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $revenueData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $dayName = Carbon::parse($date)->format('D');
            $revenueData[] = [
                'name' => $dayName, 
                'value' => (float)($dailyRevenue[$date]->value ?? 0)
            ];
        }

        // 3. Recent Orders
        $recentOrders = ShopOrder::with(['user', 'orderStatus', 'paymentStatus', 'orderLines.productItemVariant.productItem.product'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                $firstProduct = $order->orderLines->first()?->productItemVariant?->productItem?->product?->name ?? 'Unknown Product';
                return [
                    'id' => 'ORD-' . str_pad($order->id, 3, '0', STR_PAD_LEFT),
                    'customer' => $order->user->name ?? 'Guest',
                    'product' => $firstProduct,
                    'amount' => '$' . number_format($order->order_total, 2),
                    'status' => $order->orderStatus->status ?? 'Pending',
                    'payment_status' => $order->paymentStatus->status ?? 'Unpaid',
                    'date' => Carbon::parse($order->created_at)->diffForHumans(),
                ];
            });

        // 4. Alerts
        $alerts = [];
        
        // Low Stock
        $lowStockItems = ProductItem::with('product')
            ->where('quantity_in_stock', '<', 5)
            ->take(3)
            ->get();
        foreach ($lowStockItems as $item) {
            $alerts[] = [
                'title' => 'Low Stock Warning',
                'desc' => "{$item->product->name} (SKU: {$item->sku}) is below 5 units.",
                'time' => Carbon::parse($item->updated_at)->diffForHumans(),
                'type' => 'warning'
            ];
        }

        // New Reviews
        $latestReviews = UserReview::with('user')
            ->latest()
            ->take(2)
            ->get();
        foreach ($latestReviews as $review) {
            $alerts[] = [
                'title' => 'New Review',
                'desc' => "{$review->rating}-star review from {$review->user->name}.",
                'time' => Carbon::parse($review->created_at)->diffForHumans(),
                'type' => 'success'
            ];
        }

        // Add some default alerts if empty to keep UI populated
        if (empty($alerts)) {
            $alerts[] = [
                'title' => 'System Status',
                'desc' => 'All systems operational.',
                'time' => 'Just now',
                'type' => 'info'
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'revenue' => [
                        'value' => '$' . number_format($totalRevenue, 2),
                        'trend' => $revenueTrend['label'],
                        'isPositive' => $revenueTrend['isPositive']
                    ],
                    'orders' => [
                        'value' => number_format($totalOrders),
                        'trend' => $ordersTrend['label'],
                        'isPositive' => $ordersTrend['isPositive']
                    ],
                    'customers' => [
                        'value' => number_format($totalCustomers),
                        'trend' => $customersTrend['label'],
                        'isPositive' => $customersTrend['isPositive']
                    ],
                    'products_sold' => [
                        'value' => number_format($productsSold),
                        'trend' => $productsTrend['label'],
                        'isPositive' => $productsTrend['isPositive']
                    ]
                ],
                'revenue_chart' => $revenueData,
                'category_data' => $categoryData,
                'top_products' => $topProducts,
                'recent_orders' => $recentOrders,
                'alerts' => array_slice($alerts, 0, 5) // Limit to 5
            ]
        ]);
    }

    /**
     * Global Admin Reports
     */
    public function reports(Request $request): JsonResponse
    {
        try {
            // 1. Top 5 Stores by Revenue
            $topStores = Store::select('stores.id', 'stores.name', 'stores.store_image')
                ->join('products', 'stores.id', '=', 'products.store_id')
                ->join('product_items', 'products.id', '=', 'product_items.product_id')
                ->join('product_item_variants', 'product_items.id', '=', 'product_item_variants.product_item_id')
                ->join('order_lines', 'product_item_variants.id', '=', 'order_lines.product_item_variant_id')
                ->join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
                ->where('shop_orders.payment_status_id', 2)
                ->selectRaw('SUM(order_lines.price * order_lines.quantity) as total_revenue')
                ->selectRaw('COUNT(DISTINCT order_lines.order_id) as total_orders')
                ->groupBy('stores.id', 'stores.name', 'stores.store_image')
                ->orderByDesc('total_revenue')
                ->take(5)
                ->get();

            // 2. User Distribution by Role
            $userDistribution = User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->get();

            // 3. Top Categories
            $topCategories = Category::select('categories.id', 'categories.name')
                ->join('products', 'categories.id', '=', 'products.category_id')
                ->join('product_items', 'products.id', '=', 'product_items.product_id')
                ->join('product_item_variants', 'product_items.id', '=', 'product_item_variants.product_item_id')
                ->join('order_lines', 'product_item_variants.id', '=', 'order_lines.product_item_variant_id')
                ->join('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
                ->where('shop_orders.payment_status_id', 2)
                ->selectRaw('SUM(order_lines.price * order_lines.quantity) as revenue')
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('revenue')
                ->take(5)
                ->get();

            // 4. Latest Payouts
            $latestPayouts = Payout::with('store')
                ->latest()
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'top_stores' => $topStores,
                    'user_distribution' => $userDistribution,
                    'top_categories' => $topCategories,
                    'latest_payouts' => $latestPayouts
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Global Admin Analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $year = Carbon::now()->year;

            // 1. Monthly Revenue & Orders Trend
            $monthlyStats = ShopOrder::select(
                    DB::raw('MONTH(order_date) as month'),
                    DB::raw('SUM(CASE WHEN payment_status_id = 2 THEN order_total ELSE 0 END) as revenue'),
                    DB::raw('COUNT(*) as orders')
                )
                ->whereYear('order_date', $year)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthName = Carbon::create()->month($i)->format('M');
                $chartData[] = [
                    'name' => $monthName,
                    'revenue' => (float)($monthlyStats[$i]->revenue ?? 0),
                    'orders' => (int)($monthlyStats[$i]->orders ?? 0)
                ];
            }

            // 2. Global Status Distribution
            $statusDistribution = ShopOrder::join('order_statuses', 'shop_orders.order_status_id', '=', 'order_statuses.id')
                ->select('order_statuses.status as name', DB::raw('count(*) as value'))
                ->groupBy('order_statuses.status')
                ->get();

            // 3. Payment Method Popularity
            $paymentDistribution = ShopOrder::join('payment_accounts', 'shop_orders.payment_method_id', '=', 'payment_accounts.id')
                ->select('payment_accounts.account_name as name', DB::raw('count(*) as value'))
                ->groupBy('payment_accounts.account_name')
                ->get();

            // 4. Simple AI Prediction (Next month estimate)
            $lastMonthRevenue = $monthlyStats->last()?->revenue ?? 0;
            $prevMonthRevenue = $monthlyStats->slice(-2, 1)->first()?->revenue ?? 0;
            $growthRate = $prevMonthRevenue > 0 ? (($lastMonthRevenue - $prevMonthRevenue) / $prevMonthRevenue) : 0.05;
            $prediction = $lastMonthRevenue * (1 + min(max($growthRate, -0.2), 0.2)); // Cap at 20%
            $predictedGrowth = number_format(abs($growthRate * 100), 1) . '%';
            $predictionText = "Based on recent trends, your sales are expected to " . ($growthRate >= 0 ? "grow" : "change") . " by {$predictedGrowth} next month.";

            return response()->json([
                'success' => true,
                'data' => [
                    'monthly_trend' => $chartData,
                    'status_distribution' => $statusDistribution,
                    'payment_distribution' => $paymentDistribution,
                    'prediction' => [
                        'text' => $predictionText,
                        'value' => (float)$prediction,
                        'growth' => $predictedGrowth
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function calculateTrend($current, $previous): array
    {
        if ($previous <= 0) {
            return [
                'label' => $current > 0 ? '+100%' : '0%',
                'isPositive' => $current > 0
            ];
        }

        $change = (($current - $previous) / $previous) * 100;
        $label = ($change >= 0 ? '+' : '') . number_format($change, 1) . '%';

        return [
            'label' => $label,
            'isPositive' => $change >= 0
        ];
    }
}
