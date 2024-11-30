<?php

namespace App\Http\Controllers;

use App\Mail\SendRequestPasswordMail;
use App\Models\User;
use App\Traits\ApiResponser;
use App\Traits\HasPhoneFieldTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    use ApiResponser, HasPhoneFieldTrait;

    public function validateOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'otp' => 'required|string|exists:users,otp',
                'email' => 'required|string|exists:users,email'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            User::where('email', $request->email)->update([
                'otp' => null,
                'isVerified' => true,
                'email_verified_at' => Carbon::now()
            ]);
            return $this->success('Account verified successfully!', '', 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function resendOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|exists:users,email'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            //generate otp function
            $otp = generateOtp();

            $user = User::with('usermeta')->where('email', $request->email)->first();
            if ($user) {
                $user->update([
                    'otp' => $otp
                ]);

                Mail::to($user->email)->send(new SendRequestPasswordMail($user, $otp));

                return $this->success('OTP sent successfully!', ['token' => $otp], 200);
            }else{
                return $this->error('User with email was not found', 400);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'otp' => 'required|exists:users,otp',
                'email' => 'required|exists:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }
            $user = User::where('otp', $request->otp)->where('email', $request->email)->first();
            if (!$user) return $this->error('user record not found', 400);

            User::where('email', $user->email)->update([
                'otp' => null,
                'password' => Hash::make($request->password),
            ]);
            return $this->success('Password reset successfully!', null, 200);

        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }
            $user = User::find(Auth::id());
            if (!$user) return $this->error('user record not found', 400);

            User::where('email', $user->email)->update([
                'password' => Hash::make($request->password),
            ]);
            return $this->success('Password reset successfully!', null, 200);

        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
