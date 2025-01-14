<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\OneBet;
use App\Models\Predict;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\GenerateReferenceService;
use App\Services\WalletCredit;
use App\Services\WalletDebit;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function users(Request $request)
    {
        $users = User::with('wallet')->paginate($request->per_page ?? 20);
        return $this->success('user retrived', $users, 200);
    }

    public function singleUser($id)
    {
        $user = User::with(['wallet'])->where('id', $id)->first();
        return $this->success('user retrived', $user, 200);
    }

    public function blockUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->update([
                'isBlocked' => true
            ]);
            return $this->success('User blocked successfully', $user, 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function unblockUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->update([
                'isBlocked' => false
            ]);
            return $this->success('User unblocked successfully', $user, 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function withdrawalRequest(Request $request)
    {
        if ($request->status) {
            $withdrawal = Withdrawal::with(['bank', 'user'])->where('status', $request->status)->paginate($request->per_page ?? 20);
        } else {
            $withdrawal = Withdrawal::with(['bank', 'user'])->paginate($request->per_page ?? 20);
        }
        return $this->success('Record retrieved', $withdrawal, 200);
    }

    public function updateWithdrawalStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:approved,declined',
                'remark' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $withdrawal = Withdrawal::findOrFail($id);
            $withdrawal->update([
                'status' => $request->status,
                'remark' => $request->remark
            ]);

            if ($request->status == 'declined') {
                $reference = (new GenerateReferenceService())->generateReference();
                $payload = [
                    'user_id' => $withdrawal->user_id,
                    'reference' => $reference,
                    'amount' => $withdrawal->amount,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'narration' => 'Admin declined Wallet withdrawal request',
                    'trx_source' => 'Wallet'
                ];
                $logTransaction = (new WalletCredit())->createCredit($payload);
                if (!$logTransaction) {
                    return $this->error('Could not process withdrawal, contact support!!!', 400);
                }
            }
            return $this->success('Withdrawal request updated successfully', [], 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return $this->success('User record deleted successfully', [], 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
