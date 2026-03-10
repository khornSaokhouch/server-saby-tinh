<?php

namespace App\Http\Controllers;

use App\Models\UserPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserPaymentController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $query = UserPayment::with(['user', 'order', 'paymentMethod', 'paymentStatus']);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        return $this->successResponse(
            $query->latest()->get(),
            'User payments retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|exists:shop_orders,id',
            'payment_method_id' => 'required|exists:payment_accounts,id',
            'payment_status_id' => 'required|exists:payment_statuses,id',
            'transaction_id' => 'nullable|string|max:100',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|max:10',
            'paid_at' => 'nullable|date',
        ]);

        $data['user_id'] = Auth::id();

        $userPayment = UserPayment::create($data);

        return $this->successResponse(
            $userPayment->load(['user', 'order', 'paymentMethod', 'paymentStatus']),
            'User payment created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $userPayment = UserPayment::with(['user', 'order', 'paymentMethod', 'paymentStatus'])->find($id);

        if (!$userPayment) {
            return $this->errorResponse('User payment not found', 404);
        }

        // Check if the user is authorized to view this payment
        if (Auth::user()->role !== 'admin' && $userPayment->user_id !== Auth::id()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        return $this->successResponse($userPayment);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userPayment = UserPayment::find($id);

        if (!$userPayment) {
            return $this->errorResponse('User payment not found', 404);
        }

        // Only admin can update payments
        if (Auth::user()->role !== 'admin') {
            return $this->errorResponse('Unauthorized', 403);
        }

        $data = $request->validate([
            'payment_status_id' => 'sometimes|required|exists:payment_statuses,id',
            'transaction_id' => 'nullable|string|max:100',
            'amount' => 'sometimes|required|numeric',
            'currency' => 'sometimes|required|string|max:10',
            'paid_at' => 'nullable|date',
        ]);

        $userPayment->update($data);

        // Sync with ShopOrder
        if ($userPayment->payment_status_id == 2) { // 2 = Success
            $order = \App\Models\ShopOrder::find($userPayment->order_id);
            if ($order) {
                $order->update(['payment_status_id' => 2]);
                $order->recordPromoUsage();
                $order->updateInvoiceStatus();
            }
        }

        return $this->successResponse(
            $userPayment->load(['user', 'order', 'paymentMethod', 'paymentStatus']),
            'User payment updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $userPayment = UserPayment::find($id);

        if (!$userPayment) {
            return $this->errorResponse('User payment not found', 404);
        }

        if (Auth::user()->role !== 'admin') {
            return $this->errorResponse('Unauthorized', 403);
        }

        $userPayment->delete();

        return $this->successResponse(
            null,
            'User payment deleted successfully'
        );
    }
}
