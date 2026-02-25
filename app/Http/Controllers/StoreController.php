<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\ImageKitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    private ImageKitService $imageKit;

    public function __construct(ImageKitService $imageKit)
    {
        $this->imageKit = $imageKit;
    }

    /**
     * List all stores
     * Admin: all stores
     * User: only their store
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Store::with(['user.companyInfo.address']);

            if ($user && $user->role === 'owner') {
                $query->where('user_id', $user->id);
            }

            $stores = $query->latest()->get();

            return response()->json([
                'data' => $stores,
                'message' => 'Stores retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


     public function show(string $idOrName): JsonResponse
    {
        try {
            $query = Store::with(['user.profile', 'user.companyInfo.address']);
            
            // Handle URL encoding and hyphenated slugs
            $decoded = urldecode($idOrName);

            if (is_numeric($decoded)) {
                $store = $query->find((int)$decoded);
            } else {
                // Try exact match, then replace hyphens with spaces for slug matching
                $nameWithSpaces = str_replace('-', ' ', $decoded);
                $store = $query->where('name', 'LIKE', $decoded)
                               ->orWhere('name', 'LIKE', $nameWithSpaces)
                               ->first();
            }

            if (!$store) {
                return $this->errorResponse('Store not found', 404);
            }

            // Note: Public access allowed, but owners still restricted when authenticated
            $user = Auth::guard('api')->user();
            if ($user && $user->role === 'owner' && $store->user_id !== $user->id) {
                // If they are an owner trying to see someone else's store, 
                // we allow VIEWING but maintain other restrictions.
                // For 'show', we'll allow it for public/other owners.
            }

            return $this->successResponse($store, 'Store details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create a store
     * Only one store per regular user
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'store_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Prevent multiple stores per regular user
        if ($user->role !== 'admin' && Store::where('user_id', $user->id)->exists()) {
            return $this->errorResponse('You already have a store.', 403);
        }

        $data = [
            'name'    => $request->name,
            'user_id' => $user->id, // set user_id from auth
        ];

        // Upload image if provided
        if ($request->hasFile('store_image')) {
            $data['store_image'] = $this->imageKit->upload(
                $request->file('store_image'),
                'store_' . $user->id . '_' . time(),
                'stores'
            );
        }

        $store = Store::create($data);

        return $this->successResponse($store, 'Store created successfully', 201);
    }

    /**
     * Update a store
     * Admin: can update any store
     * User: can update only their own store
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = Store::find($id);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $user = Auth::user();
        if ($user->role !== 'admin' && $store->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized to update this store', 403);
        }

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'store_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->has('name')) {
            $store->name = $request->name;
        }

        if ($request->hasFile('store_image')) {
            // Delete old image
            if ($store->store_image) {
                $this->imageKit->delete($store->store_image);
            }

            // Upload new image
            $store->store_image = $this->imageKit->upload(
                $request->file('store_image'),
                'store_' . $store->user_id . '_' . time(),
                'stores'
            );
        }

        $store->save();

        return $this->successResponse($store, 'Store updated successfully');
    }

    /**
     * Delete a store
     * Admin: can delete any store
     * User: can delete only their own store
     */
    public function destroy(int $id): JsonResponse
    {
        $store = Store::find($id);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $user = Auth::user();
        if ($user->role !== 'admin' && $store->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized to delete this store', 403);
        }

        // Delete image from ImageKit
        if ($store->store_image) {
            $this->imageKit->delete($store->store_image);
        }

        $backup = $store;
        $store->delete();

        return $this->successResponse($backup, 'Store deleted successfully');
    }

    /** ===================== PRIVATE HELPERS ===================== */

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
