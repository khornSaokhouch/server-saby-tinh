<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    public function index(): JsonResponse
    {
        $promotions = Promotion::with('categories')->latest()->get();
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
             'category_ids'       => 'nullable|array',
            'category_ids.*'     => 'exists:categories,id'
        ]);

        $promotion = Promotion::create($data);
        if (!empty($data['category_ids'])) {
            $promotion->categories()->sync($data['category_ids']);
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
             'category_ids'       => 'nullable|array',
            'category_ids.*'     => 'exists:categories,id'
        ]);

        $promotion->update($data);
        if (isset($data['category_ids'])) {
            $promotion->categories()->sync($data['category_ids'] ?? []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Promotion updated successfully',
            'data'    => $promotion->load('categories')
        ]);
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
