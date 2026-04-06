<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\ImageKitService;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private ImageKitService $imageKit;

    public function __construct(ImageKitService $imageKit)
    {
        $this->imageKit = $imageKit;
    }

    /** ===================== CRUD ===================== */

    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(User::with('profile')->get());
    }

     public function show(int $id): \Illuminate\Http\JsonResponse
    {
        $user = User::with(['profile', 'store', 'paymentAccounts'])->find($id);
        if (!$user) return $this->errorResponse('User not found', 404);
        return $this->successResponse($user);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:6',
            'phone_number'  => 'nullable|string',
            'role'          => 'nullable|in:user,admin,owner',
            'bio'           => 'nullable|string',
            'image_profile' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            return DB::transaction(function () use ($request, $data) {
                $user = User::create([
                    'name'         => $data['name'],
                    'email'        => $data['email'],
                    'phone_number' => $data['phone_number'] ?? null,
                    'password'     => $data['password'],
                    'role'         => $data['role'] ?? 'user',
                ]);

                // Upload with folder
                $imageUrl = null;
                if ($request->hasFile('image_profile')) {
                    $imageUrl = $this->imageKit->upload(
                        $request->file('image_profile'), 
                        'user_' . time(),
                        'profile-image' // Folder name
                    );
                }

                $user->profile()->create([
                    'bio'           => $data['bio'] ?? null,
                    'profile_image' => $imageUrl,
                ]);

                return $this->successResponse($user->load('profile'), 'User created successfully', 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

     public function updateProfile(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $currentUser = auth()->user();
        if ($currentUser->role !== 'admin' && $currentUser->id !== $id) {
            return $this->errorResponse('Unauthorized to update this profile', 403);
        }

        $user = User::find($id);
        if (!$user) return $this->errorResponse('User not found', 404);

        $data = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'email'         => 'sometimes|email|unique:users,email,' . $id,
            'phone_number'  => 'nullable|string',
            'password'      => 'nullable|min:6',
            'bio'           => 'nullable|string',
            'image_profile' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // 1. Update User Table
            if ($request->has('name')) $user->name = $data['name'];
            if ($request->has('email')) $user->email = $data['email'];
            if ($request->has('phone_number')) $user->phone_number = $data['phone_number'];
            if (!empty($data['password'])) $user->password = $data['password'];
            $user->save();

            // 2. Update Profile Table
            $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);
            
            if (isset($data['bio'])) {
                $profile->bio = $data['bio'];
            }

            if ($request->hasFile('image_profile')) {
                $url = $this->imageKit->upload(
                    $request->file('image_profile'), 
                    'user_' . $user->id . '_' . time(),
                    'profile-image' // Folder name
                );
                
                if ($url) {
                    $profile->profile_image = $url;
                }
            }
            
            $profile->save();

            DB::commit();

            return $this->successResponse($user->load('profile'), 'Profile updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Update failed: ' . $e->getMessage(), 500);
        }
    }

   public function destroy(int $id): \Illuminate\Http\JsonResponse
{
    $user = User::find($id);
    if (!$user) return $this->errorResponse('User not found', 404);

    $user->delete();

    // Return just the ID so the frontend knows which one to remove from the list
    return $this->successResponse(['id' => $id], 'User deleted successfully');
}

    /** ===================== ROLE MANAGEMENT ===================== */

    public function updateRole(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $user = User::find($id);
        if (!$user) return $this->errorResponse('User not found', 404);

        $data = $request->validate([
            'role' => 'required|in:user,admin,owner',
        ]);

        try {
            $user->role = $data['role'];
            $user->save();

            return $this->successResponse($user->load('profile'), 'User role updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Role update failed: ' . $e->getMessage(), 500);
        }
    }

    /** ===================== ADMIN: STORE INSIGHTS ===================== */

    public function getStoreMembers(int $storeId): \Illuminate\Http\JsonResponse
    {
        // Get users linked to a specific store via customer_list
        $team = User::with('profile')
            ->join('customer_list', 'users.id', '=', 'customer_list.user_id')
            ->where('customer_list.store_id', $storeId)
            ->select('users.*')
            ->get();

        return $this->successResponse($team);
    }

    /** ===================== TEAM MANAGEMENT ===================== */

    public function getTeamMembers(): \Illuminate\Http\JsonResponse
    {
        $owner = auth()->user();
        $store = $owner->accessible_store;

        if (!$store) {
            return $this->errorResponse('Store not found for this user', 404);
        }

        // Get users linked to this store via customer_list
        $team = User::with('profile')
            ->join('customer_list', 'users.id', '=', 'customer_list.user_id')
            ->where('customer_list.store_id', $store->id)
            ->select('users.*')
            ->get();

        return $this->successResponse($team);
    }

    public function addMember(Request $request): \Illuminate\Http\JsonResponse
    {
        $owner = auth()->user();
        $store = $owner->accessible_store;

        if (!$store) {
            return $this->errorResponse('Store not found for this user', 404);
        }

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:6',
            'phone_number'  => 'nullable|string',
            'bio'           => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($data, $store) {
                // Create user with default role 'owner' as requested
                $user = User::create([
                    'name'         => $data['name'],
                    'email'        => $data['email'],
                    'phone_number' => $data['phone_number'] ?? null,
                    'password'     => $data['password'],
                    'role'         => 'owner', 
                ]);

                $user->profile()->create([
                    'bio' => $data['bio'] ?? null,
                ]);

                // Link to store in customer_list
                DB::table('customer_list')->insert([
                    'store_id'   => $store->id,
                    'user_id'    => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $this->successResponse($user->load('profile'), 'Team member added successfully', 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function updateMember(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $owner = auth()->user();
        $store = $owner->accessible_store;

        // Verify membership
        $isMember = DB::table('customer_list')
            ->where('store_id', $store->id)
            ->where('user_id', $id)
            ->exists();

        if (!$isMember) {
            return $this->errorResponse('Access denied. Member not found in your team.', 403);
        }

        $user = User::find($id);
        if (!$user) return $this->errorResponse('User not found', 404);

        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'email'        => 'sometimes|email|unique:users,email,' . $id,
            'phone_number' => 'nullable|string',
            'password'     => 'nullable|min:6',
            'bio'          => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            if (isset($data['name'])) $user->name = $data['name'];
            if (isset($data['email'])) $user->email = $data['email'];
            if (isset($data['phone_number'])) $user->phone_number = $data['phone_number'];
            if (!empty($data['password'])) $user->password = $data['password'];
            $user->save();

            if (isset($data['bio'])) {
                $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);
                $profile->bio = $data['bio'];
                $profile->save();
            }

            DB::commit();
            return $this->successResponse($user->load('profile'), 'Team member updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function batchDeleteMembers(Request $request): \Illuminate\Http\JsonResponse
    {
        $owner = auth()->user();
        $store = $owner->accessible_store;
        
        $ids = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:users,id'
        ])['ids'];

        // Filter IDs to ensure they belong to this owner's store
        $validIds = DB::table('customer_list')
            ->where('store_id', $store->id)
            ->whereIn('user_id', $ids)
            ->pluck('user_id')
            ->toArray();

        if (empty($validIds)) {
            return $this->errorResponse('No valid members selected for deletion', 400);
        }

        try {
            DB::transaction(function () use ($validIds) {
                // Delete users (Cascade will handle customer_list if setup, otherwise manual)
                User::whereIn('id', $validIds)->delete();
            });

            return $this->successResponse($validIds, count($validIds) . ' team members deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    protected function successResponse($data, $message = 'Success', $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    protected function errorResponse($message = null, $status = 400)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}