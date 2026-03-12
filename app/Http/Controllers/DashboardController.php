<?php

namespace App\Http\Controllers;

use App\Models\ShopOrder;
use App\Models\User;
use App\Models\ProductItem;
use App\Models\OrderLine;
use App\Models\UserReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // 1. Totals
        $totalRevenue = ShopOrder::sum('order_total');
        $totalOrders = ShopOrder::count();
        $totalCustomers = User::where('role', User::ROLE_USER)->count();
        $productsSold = OrderLine::sum('quantity');

        // New Trends Calculation (Mocked for now, but ready for real logic)
        $revenueTrend = "+12.5%"; 
        $ordersTrend = "+4.2%";
        $customersTrend = "+8.1%";
        $productsTrend = "-2.4%";

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

        // 2. Revenue Chart (Last 7 Days)
        $revenueData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayName = $date->format('D');
            $value = ShopOrder::whereDate('order_date', $date->toDateString())->sum('order_total');
            $revenueData[] = ['name' => $dayName, 'value' => (float)$value];
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
                        'trend' => $revenueTrend,
                        'isPositive' => true
                    ],
                    'orders' => [
                        'value' => number_format($totalOrders),
                        'trend' => $ordersTrend,
                        'isPositive' => true
                    ],
                    'customers' => [
                        'value' => number_format($totalCustomers),
                        'trend' => $customersTrend,
                        'isPositive' => true
                    ],
                    'products_sold' => [
                        'value' => number_format($productsSold),
                        'trend' => $productsTrend,
                        'isPositive' => false
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
}
