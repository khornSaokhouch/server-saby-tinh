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
     * Add an item to the shopping cart.
     */
    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'product_item_variant_id' => 'required|exists:product_item_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

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
        $cartItem = ShoppingCartItem::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);

        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Cart item not found'], 404);
        }

        $cartItem->update(['quantity' => $request->quantity]);

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
        $cartItem = ShoppingCartItem::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);

        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Cart item not found'], 404);
        }

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
            ShoppingCartItem::where('cart_id', $cart->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }
}
