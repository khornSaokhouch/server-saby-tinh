<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserAddressController extends Controller
{
    /**
     * Display a listing of the authenticated user's addresses.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Provide owner addresses if requested from the dashboard by a team member
            if ($request->query('context') === 'dashboard' && $user->role !== 'admin' && $user->accessible_store) {
                $ownerId = $user->accessible_store->user_id;
                $addresses = Address::where('user_id', $ownerId)->with('country')->latest()->get();
            } else {
                $addresses = Address::where('user_id', $user->id)->with('country')->latest()->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'User addresses retrieved successfully',
                'data' => $addresses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve addresses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created address for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'house_number' => 'nullable|string|max:255',
            'street'       => 'nullable|string|max:255',
            'commune'      => 'nullable|string|max:255',
            'district'     => 'nullable|string|max:255',
            'province'     => 'required|string|max:255',
            'country_id'   => 'required|exists:countries,id',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
            'is_default'   => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $user = auth()->user();
            
            $address = Address::create([
                'user_id'      => $user->id,
                'house_number' => $request->house_number,
                'street'       => $request->street,
                'commune'      => $request->commune,
                'district'     => $request->district,
                'province'     => $request->province,
                'country_id'   => $request->country_id,
                'latitude'     => $request->latitude,
                'longitude'    => $request->longitude,
            ]);

            // Sync the user_address pivot table
            DB::table('user_address')->insert([
                'user_id'    => $user->id,
                'address_id' => $address->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Address created successfully',
                'data' => $address->load('country')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create address: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified address.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'house_number' => 'nullable|string|max:255',
            'street'       => 'nullable|string|max:255',
            'commune'      => 'nullable|string|max:255',
            'district'     => 'nullable|string|max:255',
            'province'     => 'required|string|max:255',
            'country_id'   => 'required|exists:countries,id',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $user = auth()->user();
            $address = Address::where('user_id', $user->id)->findOrFail($id);

            $address->update($request->only([
                'house_number', 'street', 'commune', 'district', 'province', 'country_id', 'latitude', 'longitude'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully',
                'data' => $address->load('country')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified address.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = auth()->user();
            $address = Address::where('user_id', $user->id)->findOrFail($id);

            // Remove pivot record
            DB::table('user_address')
                ->where('user_id', $user->id)
                ->where('address_id', $address->id)
                ->delete();

            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of all user-address mappings for Admin.
     */
    public function adminIndex(): JsonResponse
    {
        try {
            $allAddresses = DB::table('addresses')
                ->join('users', 'addresses.user_id', '=', 'users.id')
                ->leftJoin('countries', 'addresses.country_id', '=', 'countries.id')
                ->select(
                    'addresses.id as address_id',
                    'addresses.user_id',
                    'addresses.created_at as linked_at',
                    'users.name as user_name',
                    'users.email as user_email',
                    'addresses.house_number',
                    'addresses.street',
                    'addresses.commune',
                    'addresses.district',
                    'addresses.province',
                    'countries.name as country_name',
                    'addresses.latitude',
                    'addresses.longitude'
                )
                ->latest('addresses.created_at')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'All user addresses retrieved successfully',
                'data' => $allAddresses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve addresses: ' . $e->getMessage()
            ], 500);
        }
    }
}
