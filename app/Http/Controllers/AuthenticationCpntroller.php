<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class AuthenticationCpntroller extends Controller
{
    public function generateOtp(Request $request){

        $request->validate(['email' => 'required|email']);
        
        $email = $request->email;
        $password = Str::random(10);
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $email,
            'password' => Hash::make($password)]
        );

        if ($user->blocked_until && $user->blocked_until > Carbon::now()) {
            return response()->json(['message' => 'You are blocked.Try again after some time.'], 429);
        }

        $lastOtp = Otp::where('email', $email)->latest()->first();
        if ($lastOtp && $lastOtp->created_at->diffInSeconds(Carbon::now()) < 60) {
            return response()->json(['message' => 'OTP already sent. Retry after 60 Secs.'], 429);
        }

        $otp = rand(100000, 999999);
        Otp::create([
            'email' => $email,
            'otp' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(5)
        ]);

        //Notification::route('mail', $email)->notify(new SendOtpNotification($otp));

        $message = $otp. 'is your OTP. Do not share this OTP with anyone.';
        $subject = "OTP Mail";
        Mail::to($email)->send(new OtpMail($otp, $subject));
        return response()->json(['message' => 'OTP sent successfully.']);
    }

    public function login(Request $request){

        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $email = $request->email;
        $otp = $request->otp;
        $otpData = Otp::where('email', $email)->latest()->first();

        if (!$otpData){
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        if($otpData->expires_at < Carbon::now()) {
            return response()->json(['message' => 'Expired OTP.'], 400);
        }

        if (!Hash::check($otp, $otpData->otp)) {
            $failedAttempts = cache("failed_attempts_$email", 0) + 1;
            cache(["failed_attempts_$email" => $failedAttempts], 3600);

            if ($failedAttempts >= 5) {
                User::where('email',$email)->update(['blocked_until' => Carbon::now()->addHour()]);
                cache()->forget("failed_attempts_$email");
                return response()->json(['message' => 'Too many failed attempts. Account is blocked for 1 hour.'], 429);
            }

            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        $user = User::where('email',$email)->first();
        cache()->forget("failed_attempts_$email");
        $otpData->delete();

        $token = JWTAuth::fromUser($user);

        return response()->json(['token' => $token]);
    }
}
