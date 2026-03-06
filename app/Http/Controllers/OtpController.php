<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserOtp;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{

    public function sendOtp(Request $request)
{
    $request->validate([
        'username' => 'required'
    ]);

    $user = User::where('email', $request->username)
                ->orWhere('phone_number', $request->username)
                ->firstOrFail();

    // Invalidate old OTPs
    UserOtp::where('user_id', $user->id)
        ->where('is_used', false)
        ->update(['is_used' => true]);

    $otp = generateOtp();

    UserOtp::create([
        'user_id' => $user->id,
        'otp' => $otp,
        'expires_at' => now()->addMinutes(5),
    ]);

    Mail::to($user->email)->send(new OtpMail($otp));

    return response()->json([
        'message' => 'OTP sent successfully'
    ]);
}

public function verifyOtp(Request $request)
{
    $request->validate([
        'username' => 'required',
        'otp' => 'required'
    ]);

    $user = User::where('email', $request->username)
                ->orWhere('phone_number', $request->username)
                ->firstOrFail();

    $otp = UserOtp::where('user_id', $user->id)
        ->where('otp', $request->otp)
        ->where('is_used', false)
        ->where('expires_at', '>', now())
        ->first();

    if (!$otp) {
        return response()->json([
            'message' => 'Invalid or expired OTP'
        ], 422);
    }

    $otp->update(['is_used' => true]);

    return response()->json([
        'message' => 'OTP verified successfully'
    ]);
}

public function resendOtp(Request $request)
{
    $request->validate([
        'username' => 'required'
    ]);

    $user = User::where('email', $request->username)
                ->orWhere('phone_number', $request->username)
                ->firstOrFail();

    $lastOtp = UserOtp::where('user_id', $user->id)
        ->latest()
        ->first();

    if ($lastOtp && $lastOtp->created_at->diffInSeconds(now()) < 60) {
        return response()->json([
            'message' => 'Please wait before resending OTP'
        ], 429);
    }

    // Invalidate old OTPs
    UserOtp::where('user_id', $user->id)
        ->where('is_used', false)
        ->update(['is_used' => true]);

    $otp = generateOtp();

    UserOtp::create([
        'user_id' => $user->id,
        'otp' => $otp,
        'expires_at' => now()->addMinutes(5),
    ]);

    Mail::to($user->email)->send(new OtpMail($otp));

    return response()->json([
        'message' => 'OTP resent successfully'
    ]);
}



    //
}
