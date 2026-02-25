<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SizeController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(Size::latest()->get(), 'Sizes retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:sizes,name',
        ]);

        $size = Size::create($data);

        return $this->successResponse($size, 'Size created successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $size = Size::find($id);
        if (!$size) return $this->errorResponse('Size not found', 404);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:sizes,name,' . $id,
        ]);

        $size->update($data);
        return $this->successResponse($size, 'Size updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $size = Size::find($id);
        if (!$size) return $this->errorResponse('Size not found', 404);

        $size->delete();
        return $this->successResponse(null, 'Size deleted successfully');
    }
}
