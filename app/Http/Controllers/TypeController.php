<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = Type::with('category')->get();
        return $this->successResponse($types);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255'
        ]);

        $type = Type::create($data);
        return $this->successResponse($type, 'Type created successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = Type::find($id);
        if (!$type) return $this->errorResponse('Type not found', 404);

        $data = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name'        => 'sometimes|string|max:255'
        ]);

        $type->update($data);
        return $this->successResponse($type, 'Type updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $type = Type::find($id);
        if (!$type) return $this->errorResponse('Type not found', 404);

        $deletedData = $type;
        $type->delete();

        return $this->successResponse($deletedData, 'Type deleted successfully');
    }

    /** ===================== PRIVATE OOP HELPERS ===================== */

    protected function successResponse($data, $message = 'Success', $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $status);
    }

    protected function errorResponse($message = null, $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}