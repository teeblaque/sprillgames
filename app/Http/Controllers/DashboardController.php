<?php

namespace App\Http\Controllers;

use App\Constants\TransactionType;
use App\Models\Bet;
use App\Models\OneBet;
use App\Models\Transaction;
use App\Models\WalletTransaction;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ApiResponser;

    public function index()
    {
        $wins = WalletTransaction::where(['user_id' => Auth::id(), 'trx_type' => TransactionType::CREDIT])->count();
        $lost = WalletTransaction::where(['user_id' => Auth::id(), 'trx_type' => TransactionType::DEBIT])->count();
        $onebetwagered = OneBet::where('user_id', Auth::id())->sum('amount');
        $specialwagered = Bet::where('user_id', Auth::id())->sum('amount');
        $one_bet_wins = OneBet::where('user_id', Auth::id())->sum('amount_earned');
        $special_bet_wins = Bet::where('user_id', Auth::id())->sum('amount_earned');

        $special = [
            'total_wins' => Bet::where(['user_id' => Auth::id(), 'status' => 'successful'])->count(),
            'total_wager' => Bet::where(['user_id' => Auth::id()])->sum('amount'),
            'total_loss' => Bet::where(['user_id' => Auth::id(), 'status' => 'failed'])->count()
        ];

        $result = [
            'wins' => $wins,
            'lost' => $lost,
            'total_wagered' => $onebetwagered + $specialwagered,
            'biggest_win' => $one_bet_wins + $special_bet_wins,
            'special_bet' => $special
        ];

        return $this->success('Record retrieved', $result);
    }

    public function transactions(Request $request)
    {
        $transactions = WalletTransaction::where('user_id', Auth::id())->paginate($request->per_page ?? 20);
        return $this->success('Record retrieved', $transactions);
    }
}
