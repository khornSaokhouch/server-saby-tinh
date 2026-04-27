<?php

namespace App\Http\Controllers;

use App\Models\PlatformFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlatformFeeController extends Controller
{
    public function index()
    {
        $fee = PlatformFee::first();
        return response()->json([
            'success' => true,
            'data' => $fee
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fee = PlatformFee::first();
        if ($fee) {
            $fee->update(['commission_percentage' => $request->commission_percentage]);
        } else {
            $fee = PlatformFee::create(['commission_percentage' => $request->commission_percentage]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Platform fee updated successfully',
            'data' => $fee
        ]);
    }
}
