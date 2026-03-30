<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ColorController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(Color::with('categories')->latest()->get(), 'Colors retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:colors,name',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $color = Color::create(['name' => $data['name']]);
        $color->categories()->sync($data['category_ids']);

        return $this->successResponse($color->load('categories'), 'Color created successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $color = Color::find($id);
        if (!$color) return $this->errorResponse('Color not found', 404);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:colors,name,' . $id,
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if (isset($data['name'])) {
            $color->update(['name' => $data['name']]);
        }

        if (isset($data['category_ids'])) {
            $color->categories()->sync($data['category_ids']);
        }

        return $this->successResponse($color->load('categories'), 'Color updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $color = Color::find($id);
        if (!$color) return $this->errorResponse('Color not found', 404);

        $color->delete();
        return $this->successResponse(null, 'Color deleted successfully');
    }
}
