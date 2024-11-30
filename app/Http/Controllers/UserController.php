<?php

namespace App\Http\Controllers;

use App\Models\User;
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
}
