<?php

namespace App\Http\Controllers;

use App\Models\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentAccountController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        $query = PaymentAccount::query()->where('status', true);

        // If user is not admin, they should see "Platform" accounts (those belonging to admins)
        // This allows customers to see which bank/method to pay TO.
        if ($user->role !== 'admin') {
            $query->whereHas('user', function($q) {
                $q->where('role', 'admin');
            });
        }

        $accounts = $query->latest()->get();
        return $this->successResponse($accounts, 'Payment protocols retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_id'   => 'required|string|max:255',
            'type_value'   => 'required|string|max:255',
            'account_city' => 'nullable|string|max:255',
            'currency'     => 'required|in:KHR,USD',
            'status'       => 'nullable|boolean',
            'user_id'      => 'nullable|exists:users,id', // Allow admin to specify user_id
        ]);

        // If not admin, force user_id to self
        if ($user->role !== 'admin') {
            $data['user_id'] = $user->id;
        } else {
            // If admin and no user_id provided, default to self
            $data['user_id'] = $data['user_id'] ?? $user->id;
        }

        $paymentAccount = PaymentAccount::create($data);

        return $this->successResponse($paymentAccount, 'Payment account created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $query = PaymentAccount::where('id', $id);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $account = $query->first();

        if (!$account) return $this->errorResponse('Payment account not found or unauthorized', 404);

        $data = $request->validate([
            'account_name' => 'sometimes|string|max:255',
            'account_id'   => 'sometimes|string|max:255',
            'type_value'   => 'sometimes|string|max:255',
            'account_city' => 'nullable|string|max:255',
            'currency'     => 'sometimes|in:KHR,USD',
            'status'       => 'nullable|boolean',
            'user_id'      => 'nullable|exists:users,id',
        ]);

        // Only admin can change ownership
        if ($user->role !== 'admin') {
            unset($data['user_id']);
        }

        $account->update($data);
        return $this->successResponse($account, 'Payment account updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $query = PaymentAccount::where('id', $id);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $account = $query->first();

        if (!$account) return $this->errorResponse('Payment account not found or unauthorized', 404);

        $account->delete();
        return $this->successResponse(null, 'Payment account deleted');
    }
}
