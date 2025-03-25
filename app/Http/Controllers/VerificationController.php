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
                'pin_id' => 'required',
                'pin' => 'required',
                'phone' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $params = [
                'pin' => $request->pin,
                'pin_id' => $request->pin_id
            ];
            $verifyToken = verifyToken($params);
            if (!$verifyToken || $verifyToken->verified != true) {
                return $this->error('OTP verification was not successful, try again', 400);
            }
            $user = User::where('phone', $request->phone)->first();
            if ($user) {
                $user->update([
                    'isVerified' => true,
                    'email_verified_at' => Carbon::now(),
                    'otp' => null,
                ]);
            }
            return $this->success('Account verified successfully', [], 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function sendToAdmin(Request $request)
    {
        try {
            // 9036483270
            $params = [
                'phone_number' => $this->getPhoneNumberWithDialingCode('8061249343', '+234'),
            ];

            $response = sendVerOTP($params);
            if (isset($response->smsStatus) && $response->smsStatus == "Message Sent") {
                return $this->success('We have sent a token to admin phone', $response, 200);
            } else {
                if ($response->message == 'Insufficient balance') {
                    return $this->error('Service unavailable, try again!!!', 400);
                }
                return $this->error($response->message, 400);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function resendOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            // //generate otp function
            // $otp = generateOtp();

            $user = User::where('phone', $request->phone)->first();
            if (!$user) {
                return $this->error('User with phone was not found', 400);
            }

            $params = [
                'phone_number' => $this->getPhoneNumberWithDialingCode($user->phone, '+234'),
            ];

            $response = sendVerOTP($params);
            if (isset($response->smsStatus) && $response->smsStatus == "Message Sent") {
                return $this->success('We have sent a token to your phone number', $response, 200);
            } else {
                if ($response->message == 'Insufficient balance') {
                    return $this->error('Service unavailable, try again!!!', 400);
                }
                return $this->error($response->message, 400);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }
            $user = User::where('phone', $request->phone)->first();
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
