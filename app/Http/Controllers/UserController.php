<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Traits\ApiResponser;
use App\Traits\HasPhoneFieldTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ApiResponser, HasPhoneFieldTrait;

    public function index()
    {
        $user = User::where('id', Auth::id())->with(['wallet'])->first();
        return $this->success('Record found', $user);
    }

    public function remove()
    {
        $user = User::findOrFail(Auth::id());
        if ($user->delete()) {
            return $this->success('Account deleted successfully');
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user()->usermeta;
        if (!$user->isemailVerified) {
            $user->update([
                'email' => $request->email
            ]);
    
            if (isset($request->firstname) || isset($request->lastname)) {
                $user->usermeta->update([
                    'firstname' => $request->firstname,
                    'lastname' => $request->lastname
                ]);
            }
        }else{
            return $this->error('You cannot update a verified mail', 400);
        }
    }

    public function transactionHistory(Request $request)
    {
        $transactions = WalletTransaction::where('user_id', Auth::id())->latest()->paginate($request->limit ?? 20);
        return $this->success('Record retrieved', $transactions);
    }
}
