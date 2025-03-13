<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\OneBet;
use App\Models\Paystack;
use App\Models\Predict;
use App\Models\User;
use App\Models\UserBank;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\GenerateReferenceService;
use App\Services\WalletCredit;
use App\Services\WalletDebit;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        if ($request->search) {
            $users = User::with('wallet')->where('first_name', $request->search)->orWhere('last_name', $request->search)->paginate($request->per_page ?? 20);
        }else{
            $users = User::with('wallet')->paginate($request->per_page ?? 20);
        }
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

    // public function withdrawalRequest(Request $request)
    // {
    //     if ($request->status) {
    //         $withdrawal = Withdrawal::with(['bank', 'user'])->where('status', $request->status)->paginate($request->per_page ?? 20);
    //     } else {
    //         $withdrawal = Withdrawal::with(['bank', 'user'])->paginate($request->per_page ?? 20);
    //     }
    //     return $this->success('Record retrieved', $withdrawal, 200);
    // }

    public function withdrawalRequest(Request $request)
    {
        $query = Withdrawal::with(['bank', 'user']);

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply search filter if a search request is passed
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('transaction_reference', 'ILIKE', "%{$request->search}%")
                    ->orWhere('amount', 'ILIKE', "%{$request->search}%")
                    ->orWhereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'ILIKE', "%{$request->search}%")
                            ->orWhere('email', 'ILIKE', "%{$request->search}%");
                    })
                    ->orWhereHas('bank', function ($bankQuery) use ($request) {
                        $bankQuery->where('name', 'ILIKE', "%{$request->search}%")
                            ->orWhere('account_number', 'ILIKE', "%{$request->search}%");
                    });
            });
        }

        $withdrawal = $query->paginate($request->per_page ?? 20);

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

            DB::beginTransaction();

            $withdrawal = Withdrawal::findOrFail($id);

            if ($request->status == 'approved') {
                $bank = UserBank::where('user_id', Auth::id())->first();
                $paystack = new Paystack();
                $tx = $paystack->createTransfer($bank, $withdrawal->amount);
            }

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

            $withdrawal->update([
                'status' => $request->status,
                'remark' => $request->remark
            ]);


            DB::commit();
            return $this->success('Withdrawal request updated successfully', [], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
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

    public function balance_correction(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'correction_type' => 'required|in:up,down',
                'amount' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            if ($request->amount < 0) {
                return $this->error('Amount must be greater than 0');
            }

            $wallet = Wallet::where('user_id', $id)->first();
            if (!$wallet) return $this->error('User wallet not found');

            $wallet->update([
                'bonus_balance' => $request->correction_type == 'up' ? $wallet->bonus_balance + $request->amount : $wallet->bonus_balance - $request->amount
            ]);
            return $this->success('Bonus account updated successfully');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
