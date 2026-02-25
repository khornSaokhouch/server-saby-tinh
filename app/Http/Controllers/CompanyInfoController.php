<?php

namespace App\Http\Controllers;

use App\Models\CompanyInfo;
use App\Models\Address;
use App\Services\ImageKitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyInfoController extends Controller
{
    private ImageKitService $imageKit;

    public function __construct(ImageKitService $imageKit)
    {
        $this->imageKit = $imageKit;
    }

    /**
     * Display a listing of the companies.
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Admins see all, Owners see only theirs
            if ($user->role === 'admin') {
                $companies = CompanyInfo::with(['user', 'address.country'])->latest()->get();
            } else {
                $companies = CompanyInfo::where('user_id', $user->id)->with(['address.country'])->latest()->get();
            }

            return $this->successResponse($companies, 'Company info retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created company info.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $request->validate([
                'company_name'  => 'required|string|max:255',
                'company_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'description'   => 'nullable|string',
                'website_url'   => 'nullable|url',
                'open_time'     => 'nullable',
                'close_time'    => 'nullable',
                'facebook_url'  => 'nullable|url',
                'instagram_url' => 'nullable|url',
                'twitter_url'   => 'nullable|url',
                'linkedin_url'  => 'nullable|url',
                
                // Address fields
                'house_number'  => 'nullable|string|max:255',
                'street'        => 'nullable|string|max:255',
                'commune'       => 'nullable|string|max:255',
                'district'      => 'nullable|string|max:255',
                'province'      => 'required|string|max:255',
                'country_id'    => 'required|exists:countries,id',
                'latitude'      => 'nullable|numeric|between:-90,90',
                'longitude'     => 'nullable|numeric|between:-180,180',
                'user_id'       => 'nullable|exists:users,id',
            ]);

            return DB::transaction(function () use ($request, $user) {
                // 1. Create Address
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

                $data = $request->only([
                    'company_name', 'description', 'website_url', 'open_time', 'close_time',
                    'facebook_url', 'instagram_url', 'twitter_url', 'linkedin_url',
                ]);
                $data['user_id'] = $user->id;
                $data['address_id'] = $address->id;

                if ($request->hasFile('company_image')) {
                    $data['company_image'] = $this->imageKit->upload(
                        $request->file('company_image'),
                        'company_' . $user->id . '_' . time(),
                        'companies'
                    );
                }

                $company = CompanyInfo::create($data);
                
                // 3. Link User to Address in pivot table
                DB::table('user_address')->updateOrInsert(
                    ['user_id' => $user->id, 'address_id' => $address->id],
                    ['updated_at' => now(), 'created_at' => now()]
                );

                $company->load('address.country');

                return $this->successResponse($company, 'Company info created successfully', 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified company info.
     */
    public function show($id): JsonResponse
    {
        $company = CompanyInfo::with(['user', 'address.country'])->find($id);
        if (!$company) {
            return $this->errorResponse('Company info not found', 404);
        }

        return $this->successResponse($company, 'Company info retrieved successfully');
    }

    /**
     * Update the specified company info.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $company = CompanyInfo::find($id);
            if (!$company) {
                return $this->errorResponse('Company info not found', 404);
            }

            $user = Auth::user();
            if ($user->role !== 'admin' && $company->user_id !== $user->id) {
                return $this->errorResponse('Unauthorized to update this company info', 403);
            }

            $request->validate([
                'company_name'  => 'sometimes|string|max:255',
                'company_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'description'   => 'nullable|string',
                'website_url'   => 'nullable|url',
                'open_time'     => 'nullable',
                'close_time'    => 'nullable',
                'facebook_url'  => 'nullable|url',
                'instagram_url' => 'nullable|url',
                'twitter_url'   => 'nullable|url',
                'linkedin_url'  => 'nullable|url',

                // Address fields
                'house_number'  => 'nullable|string|max:255',
                'street'        => 'nullable|string|max:255',
                'commune'       => 'nullable|string|max:255',
                'district'      => 'nullable|string|max:255',
                'province'      => 'sometimes|string|max:255',
                'country_id'    => 'sometimes|exists:countries,id',
                'latitude'      => 'nullable|numeric|between:-90,90',
                'longitude'     => 'nullable|numeric|between:-180,180',
                'user_id'       => 'nullable|exists:users,id',
            ]);

            return DB::transaction(function () use ($request, $company, $user) {
                // 1. Update or Create Address
                $addressData = $request->only([
                    'house_number', 'street', 'commune', 'district', 'province', 'country_id', 'latitude', 'longitude'
                ]);

                if ($company->address_id) {
                    Address::where('id', $company->address_id)->update($addressData);
                    $addressId = $company->address_id;
                } else {
                    $address = Address::create(array_merge($addressData, ['user_id' => $company->user_id]));
                    $company->address_id = $address->id;
                    $addressId = $address->id;
                }

                // 2. Link User to Address in pivot table (use company owner id, not logged-in user if admin)
                DB::table('user_address')->updateOrInsert(
                    ['user_id' => $company->user_id, 'address_id' => $addressId],
                    ['updated_at' => now(), 'created_at' => now()]
                );

                $data = $request->only([
                    'company_name', 'description', 'website_url', 'open_time', 'close_time',
                    'facebook_url', 'instagram_url', 'twitter_url', 'linkedin_url',
                ]);

                if ($request->hasFile('company_image')) {
                    if ($company->company_image) {
                        $this->imageKit->delete($company->company_image);
                    }
                    $data['company_image'] = $this->imageKit->upload(
                        $request->file('company_image'),
                        'company_' . $company->user_id . '_' . time(),
                        'companies'
                    );
                }

                $company->update($data);
                $company->load('address.country');

                return $this->successResponse($company, 'Company info updated successfully');
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified company info.
     */
    public function destroy($id): JsonResponse
    {
        $company = CompanyInfo::find($id);
        if (!$company) {
            return $this->errorResponse('Company info not found', 404);
        }

        $user = Auth::user();
        if ($user->role !== 'admin' && $company->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized to delete this company info', 403);
        }

        if ($company->company_image) {
            $this->imageKit->delete($company->company_image);
        }

        $company->delete();

        return $this->successResponse(null, 'Company info deleted successfully');
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
