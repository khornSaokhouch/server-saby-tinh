<?php

namespace App\Http\Controllers;

use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShippingMethodController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(ShippingMethod::all());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0'
        ]);

        $method = ShippingMethod::create($data);

        return response()->json($method, 201);
    }

    public function show(int $id): JsonResponse
    {
        $method = ShippingMethod::find($id);

        if (!$method) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($method);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $method = ShippingMethod::find($id);

        if (!$method) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'price' => 'nullable|numeric|min:0'
        ]);

        $method->update($data);

        return response()->json($method);
    }

    public function destroy(int $id): JsonResponse
    {
        $method = ShippingMethod::find($id);

        if (!$method) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $method->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
