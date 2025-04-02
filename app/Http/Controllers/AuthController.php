<?php

namespace App\Http\Controllers;

use App\Jobs\VerifyAccount;
use App\Mail\SendLoginNotifictionMail;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\ApiResponser;
use App\Traits\HasPhoneFieldTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Spatie\Newsletter\Facades\Newsletter;
// use NewsLetter;

class AuthController extends Controller
{
    use ApiResponser, HasPhoneFieldTrait;
    
    public function createAdminUsers(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|unique:users,phone',
                'username' => 'required|unique:users,username',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:staff,admin,superadmin'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }


            $user = User::where('phone', $request->phone)->first();
            if ($user) return $this->error('Phone number is already taken');

            $user = User::create([
                'phone' => $request->phone,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'name' => $request->name,
                'role' => $request->role,
                'referred_code' => $request->referred_code,
                'email_verified_at' => Carbon::now(),
                'isVerified' => true
            ]);

            if (!Newsletter::isSubscribed($request->email)) {
                Newsletter::subscribe($request->email, [
                    'FNAME' => $request->name
                ]);
            }

            $success['user'] =  $user;

            return $this->success('Admin user Registration was successful.', $success, 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|unique:users,phone',
                'username' => 'required|unique:users,username',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'referred_code' => 'nullable|string|exists:users,referrer_code'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }


            $user = User::where('phone', $request->phone)->first();
            if ($user) return $this->error('Phone number is already taken');

            $otp = generateOtp();
            $user = User::create([
                'phone' => $request->phone,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'name' => $request->name,
                'role' => 'user',
                'otp' => $otp,
                'referred_code' => $request->referred_code
            ]);
            Wallet::create(['user_id' => $user->id]);

            if (!Newsletter::isSubscribed($request->email)) {
                Newsletter::subscribe($request->email, [
                    'FNAME' => $request->name
                ]);
            }

            // VerifyAccount::dispatchAfterResponse($user, $otp);
            $params = [
                'phone_number' => $this->getPhoneNumberWithDialingCode($request->phone, '+234'),
                'email' => $request->email,
                'otp' => $otp
            ];

            $response = sendVerOTP($params);

            $credentials = $request->only($this->username(), 'password');

            if (!Auth::attempt($credentials)) {
                return $this->error('Credential mismatch', 400);
            }

            $success['user'] =  $user;
            $success['phone_code'] = $response;
            $success['access_token'] =  $user->createToken('access_token')->plainTextToken;
            $success['refresh_token'] =  $user->createToken('refresh_token')->plainTextToken;
            $success['token_type'] = 'Bearer';

            return $this->success('User Registration was successful.', $success, 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            // if (is_numeric($request->get('email'))) {
            //     $credentials = ['phone' => $request->get('email'), 'password' => $request->get('password')];
            // } else {
            //     $credentials = $request->only($this->username(), 'password');
            // }
            $credentials = $request->only($this->username(), 'password');

            if (!Auth::attempt($credentials)) {
                return $this->error('Credential mismatch', 400);
            }

            $userapp = Auth::user();

            // Delete all existing tokens for the user
            $userapp->tokens()->delete();

            $user = User::where('id', $userapp->id)->with(['wallet'])->first();

            if ($user && $user->isBlocked) {
                return $this->error('Account blocked, contact support!!!', 400);
            }

            if ($user && !$user->isVerified) {
                // $otp = generateOtp();

                // $user->update([
                //     'otp' => $otp
                // ]);
                // VerifyAccount::dispatchAfterResponse($user, $otp);
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
            }

            Mail::to($user->email)->send(new SendLoginNotifictionMail($user));

            $success['access_token'] =  $user->createToken('access_token')->plainTextToken;
            $success['refresh_token'] =  $user->createToken('refresh_token')->plainTextToken;
            $success['token_type'] = 'Bearer';
            $success['user'] =  $user;

            return $this->success('User login successfully.', $success, 200);
        } catch (\Throwable $error) {
            return $this->error($error->getMessage(), 400);
        }
    }

    public function username()
    {
        return 'phone';
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens('access_token')->delete();
            return $this->success('You have been successfully logged out!', 200);
        } catch (\Exception $error) {
            return $this->error($error->getMessage(), 500);
        }
    }

    public function refreshToken(Request $request)
    {
        $userapp = Auth::user();
        // Delete all existing tokens for the user
        $userapp->tokens()->delete();

        $user = User::where('id', $userapp->id)->with(['wallet'])->first();
        if ($user && $user->isBlocked) {
            return $this->error('Account blocked, contact support!!!', 400);
        }
        $success['access_token'] =  $user->createToken('refresh_token')->plainTextToken;
        $success['refresh_token'] =  $user->createToken('refresh_token')->plainTextToken;
        $success['token_type'] = 'Bearer';
        $success['user'] =  $user;

        return $this->success('User login successfully.', $success, 200);
    }
}
