<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SizeController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(Size::with('categories')->latest()->get(), 'Sizes retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:sizes,name',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $size = Size::create(['name' => $data['name']]);
        $size->categories()->sync($data['category_ids']);

        return $this->successResponse($size->load('categories'), 'Size created successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $size = Size::find($id);
        if (!$size) return $this->errorResponse('Size not found', 404);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:sizes,name,' . $id,
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if (isset($data['name'])) {
            $size->update(['name' => $data['name']]);
        }

        if (isset($data['category_ids'])) {
            $size->categories()->sync($data['category_ids']);
        }

        return $this->successResponse($size->load('categories'), 'Size updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $size = Size::find($id);
        if (!$size) return $this->errorResponse('Size not found', 404);

        $size->delete();
        return $this->successResponse(null, 'Size deleted successfully');
    }
}
