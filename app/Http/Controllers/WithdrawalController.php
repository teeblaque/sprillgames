<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\BankAccount\AccountDebit;
use App\Services\GenerateReferenceService;
use App\Services\WalletCredit;
use App\Services\WalletDebit;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WithdrawalController extends Controller
{
    use ApiResponser;

    public function index()
    {
        $withdrawal = Withdrawal::with(['bank', 'user'])->where(['user_id' => Auth::id(), 'status' => 'pending'])->first();
        return $this->success('Record retrieved', $withdrawal, 200);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'user_bank_id' => 'required|exists:user_banks,id',
                'amount' => 'required',
                'remark' => 'nullable',
                'transaction_pin' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            if (!Hash::check($request->transaction_pin, Auth::user()->transaction_pin)) {
                return $this->error('Invalid transaction pin.', 400);
            }
            if ($request->amount < 2000) {
                return $this->error('Minimum withdrawal amount must be greater than N2,000', 400);
            }

            if (!(new AccountDebit)->balanceCheck($request->amount, Auth::id())) {
                return $this->error("Insufficient wallet balance");
            }
            $checkWithdrawal = Withdrawal::where(['user_id' => Auth::id(), 'status' => 'pending'])->first();
            if ($checkWithdrawal) return $this->error('You have a pending withdrawal request, kindly cancel to initiate a new one', 400);

            $reference = (new GenerateReferenceService())->generateReference();
            $payload = [
                'user_id' => Auth::id(),
                'reference' => $reference,
                'amount' => $request->amount,
                'gateway_response' => 'wallet',
                'payment_channel' => 'wallet',
                'narration' => 'Wallet withdrawal request',
                'trx_source' => 'Wallet'
            ];
            $logTransaction = (new WalletDebit())->debit($payload);
            if (!$logTransaction) {
                return $this->error('Could not process withdrawal, contact support!!!', 400);
            }

            $withdrawal = Withdrawal::create([
                'user_id' => Auth::id(),
                'user_bank_id' => $request->user_bank_id,
                'amount' => $request->amount,
                'remark' => $request->remark,
                'status' => 'pending'
            ]);
            DB::commit();
            return $this->success('Withdrawal request submitted', $withdrawal, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'pin' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            DB::beginTransaction();

            $wallet = Wallet::where('user_id', Auth::id())->first();

            $withdrawal = Withdrawal::where(['user_id' => Auth::id(), 'id' => $id])->first();
            if (!$withdrawal) return $this->error('Withdrawal request not found', 400);

            if ($withdrawal->status == 'declined' || $withdrawal->status == 'approved' || $withdrawal->status == 'cancelled') {
                return $this->error('Withdrawal request cannot be cancelled');
            }

            if (!Hash::check($request->pin, Auth::user()->transaction_pin)) {
                return $this->error('Invalid transaction pin.', 400);
            }

            $reference = (new GenerateReferenceService())->generateReference();
            $payload = [
                'user_id' => Auth::id(),
                'reference' => $reference,
                'amount' => $request->amount,
                'gateway_response' => 'wallet',
                'payment_channel' => 'wallet',
                'narration' => 'Cancelled Wallet withdrawal request',
                'trx_source' => 'Wallet'
            ];
            $logTransaction = (new WalletCredit())->createCredit($payload);
            if (!$logTransaction) {
                return $this->error('Could not process withdrawal, contact support!!!', 400);
            }

            $withdrawal->update([
                'status' => 'cancelled'
            ]);

            if ($wallet->balance_after >= 0 && $withdrawal->amount >= 0) {
                $wallet->update([
                    'balance_after' => $wallet->balance_after + $withdrawal->amount
                ]);
            }

            DB::commit();

            return $this->success('Request was cancelled successfully', [], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 500);
        }
    }
}
