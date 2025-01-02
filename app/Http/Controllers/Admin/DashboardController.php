<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\OneBet;
use App\Models\Predict;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponser;

    public function index() 
    {
        $users = User::where('role', 'user')->count();
        $current_wallet_balance = Wallet::sum('balance_after');
        $wallet_transactions = WalletTransaction::count();
        $one_on_one_bets = OneBet::count();
        $special_bet = Bet::count();
        $predict = Predict::count();

        $result = [
            'users' => $users,
            'wallet_balance' => (float)$current_wallet_balance,
            'wallet_transactions' => $wallet_transactions,
            'one_on_one' => $one_on_one_bets,
            'special_bet' => $special_bet,
            'predict' => $predict
        ];

        return $this->success('Record retrieved', $result, 200);
    }

    public function users(Request $request ) 
    {
        $users = User::paginate($request->per_page ?? 20);
        return $this->success('user retrived', $users, 200);
    }

    public function singleUser($id)
    {
        $user = User::with(['wallet'])->where('id', $id)->first();
        return $this->success('user retrived', $user, 200);
    }
}
