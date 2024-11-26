<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;

use App\Helpers\MailerClass;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function signUp()
    {
        return Inertia::render('Auth/SignUp');
    }

    public function signIn()
    {
        return Inertia::render('Auth/SignIn');
    }

    public function forgotPassword()
    {
        return Inertia::render('Auth/ForgotPass');
    }

    public function passwordResetRequest(Request $request)
    {
        $request->validate([
            'email'=> ['required'],
        ]);
        

        $user = User::whereEmail($request->email)->first();

        if(!$user){
            return redirect(route('forgot-password'))->with('error','User with this email not available');
        }

        // $token = Password::getRepository()->create($user);
        $token = md5(now());

        DB::table('password_reset_tokens')->updateOrInsert(['email' => $request->email], [
            'token' => $token,
            'email' => $request->email,
            'created_at' => now(),
        ]);

        (new MailerClass($request->email, $token))->sendForgotPasswordMail();

        return redirect(route('forgot-password'))->with('message','A password recovery mail has been sent your email.');
    }

    public function resetPassword()
    {
        return Inertia::render('Auth/ResetPass');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'firstname' => ['required'],
            'lastname'=> ['required'],
            'username'=> ['required', 'unique:users'],
            'phone'=> ['required'],
            'countryCode'=> ['required'],
            'email'=> ['required', 'unique:users'],
            'password'=> ['required', 'min:8'],
        ]);

        $uuid = md5($request->email);

        $user = User::create([
            'uuid' => $uuid,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'username' => $request->username,
            'phone' => $request->phone,
            'country_code' => $request->countryCode,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        (new MailerClass('latyf01@gmail.com', $uuid))->sendWelcomeMail();

        return redirect('/dashboard');
    }

    public function verify(Request $request)
    {
        if ($request->hasValidSignature(false)) {
            abort(401);
        }

        $token = $request->all()['amp;token'];

        $record = DB::table('password_reset_tokens')->select(["email"])->where('token', $token)->first();

        if(!$record){
            abort(401);
        }

        $user = User::where('email', $record->email)->first();

        if(!$user){
            abort(401);
        }

        return redirect()->route('password-reset')->with(['token' => $token, 'email' => $record->email,]);
    }

    public function updatePassword(Request $request)
    {

        $request->validate([
            'password'=> ['required', 'min:8'],
        ]);

        if(!($request->email && $request->token)){
            return redirect(route('password-reset'))->with('error','The password reset url is not valid');
        }

       

        User::where('email', $request->email)->update(
            [
                "password" => Hash::make($request->password)
            ]
        );

        // Nullify the old reset token by reset the reset token
        DB::table('password_reset_tokens')->updateOrInsert(['email' => $request->email], [
            'token' => md5(now()),
            'email' => $request->email,
            'created_at' => now(),
        ]);

        return redirect()->route('login')->with('message', "Password reset successfully, You can now login with your new password.");
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',   
            'password' => 'required']
        );

        $credentials = $request->only('email','password');
        if (Auth::attempt($credentials)){
            return redirect()->intended(route('dashboard'));
        }  
        return redirect(route('login'))->with('error','Wrong email or password');
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function logout(){
        Session::flush();
        Auth::logout();
        return redirect(route('login'));
    }
}
