<?php

namespace App\Http\Controllers;

use App\Models\ShopOrder;
use App\Models\OrderLine;
use App\Models\OrderHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $query = ShopOrder::with(['user', 'orderLines.productItemVariant.productItem.product.images', 'orderStatus', 'shippingMethod', 'shippingAddress', 'paymentStatus']);

        if ($user->role === User::ROLE_OWNER) {
            $user->load('store');
            if ($user->store) {
                $storeId = $user->store->id;
                $query->whereHas('orderLines.productItemVariant.productItem.product', function($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                });
            } else {
                $query->where('user_id', $user->id); 
            }
        } elseif ($user->role !== User::ROLE_ADMIN) {
            $query->where('user_id', $user->id);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_accounts,id',
            'shipping_address_id' => 'required|exists:addresses,id',
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'order_total' => 'required|numeric',
            'order_status_id' => 'required|exists:order_statuses,id',
            'order_lines' => 'required|array',
            'order_lines.*.product_item_variant_id' => 'required|exists:product_item_variants,id',
            'order_lines.*.quantity' => 'required|integer|min:1',
            'order_lines.*.price' => 'required|numeric',
        ]);

        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        DB::beginTransaction();
        try {
            $order = ShopOrder::create([
                'user_id' => $userId,
                'order_date' => now(),
                'payment_method_id' => $request->payment_method_id,
                'shipping_address_id' => $request->shipping_address_id,
                'shipping_method_id' => $request->shipping_method_id,
                'order_total' => $request->order_total,
                'order_status_id' => $request->order_status_id,
            ]);

            foreach ($request->order_lines as $line) {
                OrderLine::create([
                    'order_id' => $order->id,
                    'product_item_variant_id' => $line['product_item_variant_id'],
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                ]);
            }

            OrderHistory::create([
                'user_id' => $userId,
                'order_id' => $order->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => $order->load('orderLines')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Creation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $order = ShopOrder::with(['user', 'orderLines.productItemVariant.productItem.product.images', 'orderLines.review', 'orderStatus', 'shippingMethod', 'shippingAddress', 'paymentMethod', 'orderHistory', 'paymentStatus', 'userPayments'])
            ->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
}
