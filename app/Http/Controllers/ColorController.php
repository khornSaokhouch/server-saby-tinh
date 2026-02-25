<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ColorController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(Color::latest()->get(), 'Colors retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:colors,name',
        ]);

        $color = Color::create($data);

        return $this->successResponse($color, 'Color created successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $color = Color::find($id);
        if (!$color) return $this->errorResponse('Color not found', 404);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:colors,name,' . $id,
        ]);

        $color->update($data);
        return $this->successResponse($color, 'Color updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $color = Color::find($id);
        if (!$color) return $this->errorResponse('Color not found', 404);

        $color->delete();
        return $this->successResponse(null, 'Color deleted successfully');
    }
}
