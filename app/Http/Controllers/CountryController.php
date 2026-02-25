<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    /**
     * Display a listing of countries.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Country::latest()->get()
        ]);
    }

    /**
     * Store a newly created country.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:countries,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $country = Country::create(['name' => $request->name]);

            return response()->json([
                'success' => true,
                'message' => 'Country created successfully',
                'data' => $country
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create country: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified country.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:countries,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $country = Country::findOrFail($id);
            $country->update(['name' => $request->name]);

            return response()->json([
                'success' => true,
                'message' => 'Country updated successfully',
                'data' => $country
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update country: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified country.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $country = Country::findOrFail($id);

            // Check if country is used by any addresses
            if ($country->addresses()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete: country is linked to ' . $country->addresses()->count() . ' address(es)'
                ], 409);
            }

            $country->delete();

            return response()->json([
                'success' => true,
                'message' => 'Country deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete country: ' . $e->getMessage()
            ], 500);
        }
    }
}
