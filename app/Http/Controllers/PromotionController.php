<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PromotionController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $query = Promotion::with(['categories', 'user']);

        // Owners see only their own promotions; admins see all
        if ($user && $user->role === 'owner') {
            $query->where('user_id', $user->id);
        }

        $promotions = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $promotions]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string',
            'discount_percentage'=> 'required|integer|min:0|max:100',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date',
            'status'             => 'nullable|integer|in:0,1',
            'category_ids'       => 'nullable|array',
            'category_ids.*'     => 'exists:categories,id'
        ]);

        // Auto-assign the authenticated user
        $data['user_id'] = Auth::id();
        $data['status'] = $data['status'] ?? 1;

        $promotion = Promotion::create($data);

        if (!empty($data['category_ids'])) {
            $pivotData = [];
            foreach ($data['category_ids'] as $catId) {
                $pivotData[$catId] = [
                    'user_id' => $data['user_id'],
                    'status'  => $data['status']
                ];
            }
            $promotion->categories()->sync($pivotData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Promotion created successfully',
            'data'    => $promotion->load('categories')
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $promotion = Promotion::find($id);
        if (!$promotion) return response()->json(['success' => false, 'message' => 'Promotion not found'], 404);

        $data = $request->validate([
            'name'               => 'sometimes|string|max:255',
            'description'        => 'nullable|string',
            'discount_percentage'=> 'sometimes|integer|min:0|max:100',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date',
            'status'             => 'nullable|integer|in:0,1',
            'category_ids'       => 'nullable|array',
            'category_ids.*'     => 'exists:categories,id'
        ]);

        $promotion->update($data);
        if (isset($data['category_ids'])) {
            $pivotData = [];
            foreach ($data['category_ids'] as $catId) {
                $pivotData[$catId] = [
                    'user_id' => $promotion->user_id,
                    'status'  => $data['status'] ?? $promotion->status
                ];
            }
            $promotion->categories()->sync($pivotData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Promotion updated successfully',
            'data'    => $promotion->load('categories')
        ]);
    }

    // New methods for Category Management Page
    public function getCategories(int $id): JsonResponse
    {
        $promotion = Promotion::with('categories')->find($id);
        if (!$promotion) return response()->json(['success' => false], 404);
        return response()->json($promotion->categories);
    }

    public function attachCategory(Request $request, int $id): JsonResponse
    {
        $promotion = Promotion::find($id);
        if (!$promotion) return response()->json(['success' => false], 404);

        $data = $request->validate(['category_id' => 'required|exists:categories,id']);
        
        $promotion->categories()->syncWithoutDetaching([
            $data['category_id'] => [
                'user_id' => $promotion->user_id,
                'status'  => $promotion->status
            ]
        ]);

        return response()->json(['success' => true]);
    }

    public function detachCategory(int $id, int $categoryId): JsonResponse
    {
        $promotion = Promotion::find($id);
        if (!$promotion) return response()->json(['success' => false], 404);

        $promotion->categories()->detach($categoryId);
        return response()->json(['success' => true]);
    }

    public function destroy(int $id): JsonResponse
    {
        $promotion = Promotion::find($id);
        if (!$promotion) return response()->json(['success' => false, 'message' => 'Promotion not found'], 404);

        $promotion->categories()->detach();
        $promotion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promotion deleted successfully',
        ]);
    }
}
