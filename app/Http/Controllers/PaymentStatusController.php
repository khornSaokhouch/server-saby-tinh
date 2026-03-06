<?php

namespace App\Http\Controllers;

use App\Models\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentStatusController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            PaymentStatus::all(),
            'Payment statuses retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|max:255|unique:payment_statuses,status',
        ]);

        $paymentStatus = PaymentStatus::create($data);

        return $this->successResponse(
            $paymentStatus,
            'Payment status created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $paymentStatus = PaymentStatus::find($id);

        if (!$paymentStatus) {
            return $this->errorResponse('Payment status not found', 404);
        }

        return $this->successResponse($paymentStatus);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $paymentStatus = PaymentStatus::find($id);

        if (!$paymentStatus) {
            return $this->errorResponse('Payment status not found', 404);
        }

        $data = $request->validate([
            'status' => 'required|string|max:255|unique:payment_statuses,status,' . $id,
        ]);

        $paymentStatus->update($data);

        return $this->successResponse(
            $paymentStatus,
            'Payment status updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $paymentStatus = PaymentStatus::find($id);

        if (!$paymentStatus) {
            return $this->errorResponse('Payment status not found', 404);
        }

        $paymentStatus->delete();

        return $this->successResponse(
            null,
            'Payment status deleted successfully'
        );
    }
}
