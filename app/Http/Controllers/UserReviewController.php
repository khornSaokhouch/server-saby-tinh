<?php

namespace App\Http\Controllers;

use App\Models\UserReview;
use Illuminate\Http\Request;

class UserReviewController extends Controller
{
    public function index(Request $request)
    {
        $productId = $request->query('product_id');
        
        $query = UserReview::with(['user.profile', 'orderLine.productItemVariant.productItem.product']);

        if ($productId) {
            $query->whereHas('orderLine.productItemVariant.productItem', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        $reviews = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_line_id' => 'required|exists:order_lines,id',
            'review_text' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Check if user already reviewed this order line
        $existing = UserReview::where('user_id', $userId)
            ->where('order_line_id', $request->order_line_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this item'
            ], 400);
        }

        $review = UserReview::create([
            'user_id' => $userId,
            'order_line_id' => $request->order_line_id,
            'review_text' => $request->review_text,
            'rating' => $request->rating,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'data' => $review
        ], 201);
    }
}
