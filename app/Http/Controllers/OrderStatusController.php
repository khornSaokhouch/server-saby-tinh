<?php

namespace App\Http\Controllers;

use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderStatusController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            OrderStatus::latest()->get(),
            'Order statuses retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|max:255',
        ]);

        $orderStatus = OrderStatus::create($data);

        return $this->successResponse(
            $orderStatus,
            'Order status created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $orderStatus = OrderStatus::find($id);

        if (!$orderStatus) {
            return $this->errorResponse('Order status not found', 404);
        }

        return $this->successResponse($orderStatus);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $orderStatus = OrderStatus::find($id);

        if (!$orderStatus) {
            return $this->errorResponse('Order status not found', 404);
        }

        $data = $request->validate([
            'status' => 'required|string|max:255',
        ]);

        $orderStatus->update($data);

        return $this->successResponse(
            $orderStatus,
            'Order status updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $orderStatus = OrderStatus::find($id);

        if (!$orderStatus) {
            return $this->errorResponse('Order status not found', 404);
        }

        $orderStatus->delete();

        return $this->successResponse(
            null,
            'Order status deleted successfully'
        );
    }
}
