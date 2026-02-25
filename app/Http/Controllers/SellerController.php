<?php

namespace App\Http\Controllers;

use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Client;
use App\Services\ImageKitService;

class SellerController extends Controller
{
    protected $imageKitService;

    public function __construct(ImageKitService $imageKitService)
    {
        $this->imageKitService = $imageKitService;
    }
    // 🔹 List all sellers
    public function index()
    {
        $sellers = Seller::all();
        return response()->json($sellers);
    }

    // 🔹 Get count of pending sellers
    public function pendingCount()
    {
        $count = Seller::where('status', 'pending')->count();
        return response()->json(['count' => $count]);
    }

    // 🔹 Show single seller
    public function show($id)
    {
        $seller = Seller::findOrFail($id);
        return response()->json($seller);
    }

    // 🔹 Create new seller (only one per user)
    public function store(Request $request)
    {
        // Check if user already submitted a seller request
        $existingSeller = Seller::where('user_id', Auth::id())->first();
        if ($existingSeller) {
            return response()->json([
                'message' => 'You have already submitted a seller registration request.',
                'status' => 'exists',
            ], 400);
        }
    
        // Validate input (make 'document' required)
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'country_region' => 'required|string|max:255',
            'street_address' => 'required|string',
            'phone_number' => 'required|string|max:50',
            'document' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);
    
        // Create new seller
        $seller = Seller::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'company_name' => $request->company_name,
            'email' => $request->email,
            'country_region' => $request->country_region,
            'street_address' => $request->street_address,
            'phone_number' => $request->phone_number,
        ]);
    
        // Store document via ImageKit
        $file = $request->file('document');
        $fileName = 'seller_doc_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $this->imageKitService->upload($file, $fileName, 'sellers');

        if (!$path) {
            return response()->json(['message' => 'Failed to upload document to ImageKit.'], 500);
        }

        $seller->document_path = $path;
        $seller->save();
    
        return response()->json([
            'message' => 'Seller registration submitted successfully!',
            'seller' => $seller,
        ], 201);
    }
    
    // 🔹 Update seller
    public function update(Request $request, $id)
    {
        $seller = Seller::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'company_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'country_region' => 'sometimes|required|string|max:255',
            'street_address' => 'sometimes|required|string',
            'phone_number' => 'sometimes|required|string|max:20',
        ]);

        $seller->update($validated);

        return response()->json($seller);
    }

    // 🔹 Approve seller (Email notification)
    public function approve(Request $request, $id)
    {
        $seller = Seller::findOrFail($id);
        $user = User::findOrFail($seller->user_id);

        $seller->status = 'approved';
        $seller->save();

        $user->role = 'owner';
        $user->save();

        // 📧 Send email to seller
        Mail::raw("
✅ Congratulations!

Your seller account has been approved.

🏢 Company: {$seller->company_name}
👤 Name: {$seller->name}
📧 Email: {$seller->email}

You can now log in and manage your company profile.
        ", function ($message) use ($seller) {
            $message->to($seller->email)
                    ->subject('✅ Seller Account Approved');
        });

        return response()->json([
            'message' => 'Seller approved and email sent.',
            'seller' => $seller,
        ]);
    }

    // 🔹 Reject seller (Email notification)
    public function reject($id)
    {
        $seller = Seller::findOrFail($id);

        if ($seller->status === 'approved') {
            return response()->json(['message' => 'Approved sellers cannot be rejected.'], 400);
        }

        // 📧 Send rejection email
        Mail::raw("
❌ Unfortunately, your seller registration has been rejected.

🏢 Company: {$seller->company_name}
👤 Name: {$seller->name}
📧 Email: {$seller->email}

If you believe this was a mistake, please contact our support team.
        ", function ($message) use ($seller) {
            $message->to($seller->email)
                    ->subject('❌ Seller Registration Rejected');
        });

        // 🗑️ Cleanup ImageKit
        $this->imageKitService->delete($seller->document_path);

        $seller->delete();

        return response()->json(['message' => 'Seller rejected, deleted, and email sent.']);
    }

    // 🔹 Delete seller (admin only)
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $seller = Seller::findOrFail($id);
            // 🗑️ Cleanup ImageKit
            $this->imageKitService->delete($seller->document_path);

            $seller->delete();

            Mail::raw("
🗑️ Your seller account has been deleted by the administrator.

🏢 Company: {$seller->company_name}
👤 Name: {$seller->name}
📧 Email: {$seller->email}
            ", function ($message) use ($seller) {
                $message->to($seller->email)
                        ->subject('⚠️ Seller Account Deleted');
            });

            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => "Seller with id {$id} not found."], 404);
        }
    }
}
