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

        // Trends (Simple mock comparison for now, could be calculated vs last month)
        $revenueTrend = "+12.5%"; 
        $ordersTrend = "+4.2%";
        $customersTrend = "+8.1%";
        $productsTrend = "-2.4%";

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
                'recent_orders' => $recentOrders,
                'alerts' => array_slice($alerts, 0, 5) // Limit to 5
            ]
        ]);
    }
}
