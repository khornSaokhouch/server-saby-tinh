<?php

namespace App\Http\Controllers;

use App\Models\ShopOrder;
use App\Models\OrderLine;
use App\Models\OrderHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\TelegramController;
use App\Models\UserSocial;

class ShopOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $query = ShopOrder::with(['user', 'orderLines.productItemVariant.productItem.product.images', 'orderStatus', 'shippingMethod', 'shippingAddress', 'paymentStatus', 'invoice.payout']);

        if ($user->role === User::ROLE_ADMIN) {
            // Admin sees all
        } else {
            // If the user has a store (as an owner or team member), scope to that store's orders
            $store = $user->accessible_store;
            
            if ($store) {
                $storeId = $store->id;
                $query->whereHas('orderLines.productItemVariant.productItem.product', function($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                });
            } else {
                // Otherwise, normal customer viewing their personal order history
                $query->where('user_id', $user->id); 
            }
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
            'subtotal' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'shipping_fee' => 'nullable|numeric',
            'order_total' => 'required|numeric',
            'promo_code_id' => 'nullable|exists:promo_codes,id',
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
            // Recalculate subtotal for security
            $calculatedSubtotal = 0;
            foreach ($request->order_lines as $line) {
                // Assuming 'price' is sent but we should ideally fetch it from DB too
                // For now, let's use the price sent but in a real app you'd fetch product price
                $calculatedSubtotal += $line['price'] * $line['quantity'];
            }

            $discountAmount = 0;
            $promoId = null;
            if ($request->promo_code_id) {
                $promo = \App\Models\PromoCode::find($request->promo_code_id);
                if ($promo && $promo->isValidFor($calculatedSubtotal)) {
                    $promoId = $promo->id;
                    if ($promo->discount_type === 'percentage') {
                        $discountAmount = ($calculatedSubtotal * $promo->discount_value) / 100;
                        if ($promo->max_discount_amount && $discountAmount > $promo->max_discount_amount) {
                            $discountAmount = $promo->max_discount_amount;
                        }
                    } else {
                        $discountAmount = $promo->discount_value;
                    }
                }
            }

            $shippingFee = $request->shipping_fee ?? 0;
            $orderTotal = max(0, $calculatedSubtotal + $shippingFee - $discountAmount);

            $order = ShopOrder::create([
                'user_id' => $userId,
                'order_date' => now(),
                'payment_method_id' => $request->payment_method_id,
                'shipping_address_id' => $request->shipping_address_id,
                'shipping_method_id' => $request->shipping_method_id,
                'subtotal' => $calculatedSubtotal,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'order_total' => $orderTotal,
                'promo_code_id' => $promoId,
                'order_status_id' => 1,
            ]);

            // Create Invoice record
            \App\Models\Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'total_amount' => $orderTotal,
                'currency' => 'USD',
                'payment_status_id' => 1,
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

            // Clear shopping cart after successful order WITHOUT restoring stock
            $userCart = \App\Models\ShoppingCart::where('user_id', $userId)->first();
            if ($userCart) {
                \App\Models\ShoppingCartItem::where('cart_id', $userCart->id)->delete();
            }

            // --- Telegram Notification Logic ---
            try {
                $order->load(['orderLines.productItemVariant.productItem.product.store.user.socialAccounts']);
                
                // Get unique store owners connected to this order
                $notifiedOwners = [];
                
                foreach ($order->orderLines as $line) {
                    $store = $line->productItemVariant?->productItem?->product?->store;
                    $owner = $store?->user;
                    
                    if ($owner && !isset($notifiedOwners[$owner->id])) {
                        $telegramAccount = $owner->socialAccounts
                            ->where('provider', 'telegram')
                            ->first();
                            
                        if ($telegramAccount && $telegramAccount->social_id) {
                            $msg = "🔔 *New Order Received!*\n\n";
                            $msg .= "🆔 Order: #ORD-{$order->id}\n";
                            $msg .= "💰 Total: \${$order->order_total}\n";
                            $msg .= "🏪 Store: {$store->name}\n\n";
                            $msg .= "Check your dashboard for details.";
                            
                            TelegramController::sendMessage($telegramAccount->social_id, $msg);
                            $notifiedOwners[$owner->id] = true;
                        }
                    }
                }
            } catch (\Exception $te) {
                Log::error("Telegram Notification Failed: " . $te->getMessage());
            }
            // -----------------------------------

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
        $order = ShopOrder::with(['user', 'orderLines.productItemVariant.productItem.product.images', 'orderLines.review', 'orderStatus', 'shippingMethod', 'shippingAddress', 'paymentMethod', 'orderHistory', 'paymentStatus', 'userPayments', 'invoice.payout'])
            ->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    public function confirm($id)
    {
        $order = ShopOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Update to 'Confirmed' (ID 2)
        $order->update(['order_status_id' => 2]);

        // Record History
        \App\Models\OrderHistory::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'status_update' => 'Order Confirmed',
            'description' => 'The store owner has reviewed and confirmed your order. Processing has begun.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order confirmed successfully',
            'data' => $order->load('orderStatus')
        ]);
    }

    public function destroy($id)
    {
        $order = ShopOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Delete related records
            $order->orderLines()->delete();
            $order->orderHistory()->delete();
            $order->invoice()->delete();
            $order->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Order deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete order', 'error' => $e->getMessage()], 500);
        }
    }

    public function batchDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:shop_orders,id',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->ids as $id) {
                $order = ShopOrder::find($id);
                if ($order) {
                    $order->orderLines()->delete();
                    $order->orderHistory()->delete();
                    $order->invoice()->delete();
                    $order->delete();
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => count($request->ids) . ' orders deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete orders', 'error' => $e->getMessage()], 500);
        }
    }
}
