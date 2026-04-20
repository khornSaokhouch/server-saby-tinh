<?php

namespace App\Http\Controllers;

use App\Models\ShoppingCart;
use App\Models\ShoppingCartItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ShoppingCartController extends Controller
{
    /**
     * Get the current user's shopping cart.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $cart = ShoppingCart::with([
            'items.variant.productItem.product.images',
            'items.variant.productItem.product.category',
            'items.variant.color',
            'items.variant.size'
        ])->firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'data' => $cart
        ]);
    }

    /**
     * Add an item to the shopping cart and reduce stock.
     */
    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'product_item_variant_id' => 'required|exists:product_item_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $variant = \App\Models\ProductItemVariant::with('productItem')->find($request->product_item_variant_id);
        
        if (!$variant || $variant->quantity_in_stock < $request->quantity || $variant->productItem->quantity_in_stock < $request->quantity) {
            return response()->json([
                'success' => false, 
                'message' => 'Insufficient stock available'
            ], 422);
        }

        $user = Auth::user();
        $cart = ShoppingCart::firstOrCreate(['user_id' => $user->id]);

        $cartItem = ShoppingCartItem::where('cart_id', $cart->id)
            ->where('product_item_variant_id', $request->product_item_variant_id)
            ->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $request->quantity);
        } else {
            $cartItem = ShoppingCartItem::create([
                'cart_id' => $cart->id,
                'product_item_variant_id' => $request->product_item_variant_id,
                'quantity' => $request->quantity
            ]);
        }

        // Reduce stock for both variant and parent item
        $variant->decrement('quantity_in_stock', $request->quantity);
        $variant->productItem->decrement('quantity_in_stock', $request->quantity);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'data' => $cartItem->load([
                'variant.productItem.product.images',
                'variant.productItem.product.category',
                'variant.color',
                'variant.size'
            ])
        ], 201);
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        $cartItem = ShoppingCartItem::with('variant.productItem')->whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);

        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Cart item not found'], 404);
        }

        $oldQuantity = $cartItem->quantity;
        $newQuantity = $request->quantity;
        $diff = $newQuantity - $oldQuantity;

        if ($diff > 0) {
            // Check if more stock is available
            if ($cartItem->variant->quantity_in_stock < $diff || $cartItem->variant->productItem->quantity_in_stock < $diff) {
                return response()->json(['success' => false, 'message' => 'Insufficient stock available'], 422);
            }
            $cartItem->variant->decrement('quantity_in_stock', $diff);
            $cartItem->variant->productItem->decrement('quantity_in_stock', $diff);
        } elseif ($diff < 0) {
            // Restore stock
            $restoredAmount = abs($diff);
            $cartItem->variant->increment('quantity_in_stock', $restoredAmount);
            $cartItem->variant->productItem->increment('quantity_in_stock', $restoredAmount);
        }

        $cartItem->update(['quantity' => $newQuantity]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated',
            'data' => $cartItem
        ]);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $id): JsonResponse
    {
        $user = Auth::user();
        $cartItem = ShoppingCartItem::with('variant.productItem')->whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);

        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Cart item not found'], 404);
        }

        // Restore stock for both variant and parent item
        $cartItem->variant->increment('quantity_in_stock', $cartItem->quantity);
        $cartItem->variant->productItem->increment('quantity_in_stock', $cartItem->quantity);

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    }

    /**
     * Clear all items from the cart.
     */
    public function clearCart(): JsonResponse
    {
        $user = Auth::user();
        $cart = ShoppingCart::where('user_id', $user->id)->first();

        if ($cart) {
            $items = ShoppingCartItem::with('variant.productItem')->where('cart_id', $cart->id)->get();
            foreach ($items as $item) {
                $item->variant->increment('quantity_in_stock', $item->quantity);
                $item->variant->productItem->increment('quantity_in_stock', $item->quantity);
                $item->delete();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }
}
