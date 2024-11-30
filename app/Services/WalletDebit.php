<?php

namespace App\Services;

use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Models\WalletTransaction;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponser;
use App\Services\GenerateReferenceService;
use App\Services\Notification\FundWalletNotificationService;
use Illuminate\Support\Facades\Auth;



class WalletDebit
{
    use ApiResponser;
    /**
     * Credit user wallet.
     */
    public function debit($data){
        try{
            if ($data['amount'] <= 0) {
                return $this->error('Invalid amount', 400);
            }

            DB::beginTransaction();

            $wallet = Wallet::where('user_id', $data['user_id'])->lockForUpdate()->firstOrFail();
            $balance_before = (float)($wallet->balance_after);
            $balance_after = (float)($wallet->balance_after) - (float)($data['amount']);
            $reference = ($data['reference']) ??  (new GenerateReferenceService())->generateReference();

            // update balance_before and balance_after for wallet
            $wallet->balance_before  =  $balance_before;
            $wallet->balance_after = $balance_after;
            $wallet->save();

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' =>   $data['user_id'],
                'trx_reference' =>  $reference,
                'trx_type' => TransactionType::DEBIT,
                'trx_status' => TransactionStatus::SUCCCES,
                'trx_source' => $data['trx_source'], // data must have
                'amount' =>  $data['amount'], // data must have
                'balance_before' =>  $balance_before,
                'balance_after' =>   $balance_after,
                'ip_address' =>  $data['ip_address'] ?? null,
                'domain' =>   $data['domain'] ?? null,
                'narration' =>   $data['narration'] ?? null,
                'payment_channel' => $data['payment_channel'] ?? null,
                'is_active' =>  true,
            ]);

            DB::commit();

             // Notification
            //  (new FundWalletNotificationService())->notify($transaction);

            $msg = "Wallet ".$wallet->id ?? null." has been debited successfully for user ".$data['user_id'] ?? null;
            // info($msg);

            $msg = "Approved or completed successfully.";
            return $transaction;
        }catch(\Exception $e){
            $msg = $e->getMessage();
            return $this->error($msg, 400);
        }
    }

    public function debitAdmin($data){
        try{
            DB::beginTransaction();

            $wallet = Wallet::where('user_id', $data['user_id'])->lockForUpdate()->firstOrFail();
            // $balance_before = (float)($wallet->balance_after);
            // $balance_after = (float)($balance_before) - (float)($data['amount']);
            $reference = ($data['reference']) ??  (new GenerateReferenceService())->generateReference();;

            // update balance_before and balance_after for wallet
            // $wallet->balance_before  =  $balance_before;
            // $wallet->balance_after = $balance_after;
            // $wallet->save();

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' =>   $data['user_id'],
                'trx_reference' =>  $reference,
                'trx_type' => TransactionType::DEBIT,
                'trx_status' => $data['trx_status'] == 'approved' ? TransactionStatus::SUCCCES : TransactionStatus::FAILED,
                'trx_source' => $data['trx_source'], // data must have
                'amount' =>  $data['amount'], // data must have
                'balance_before' =>  $wallet->balance_before,
                'balance_after' =>   $wallet->balance_after,
                'ip_address' =>  $data['ip_address'] ?? null,
                'domain' =>   $data['domain'] ?? null,
                'narration' =>   $data['narration'] ?? null,
                'payment_channel' => $data['payment_channel'] ?? null,
                'is_active' =>  true,
            ]);

            DB::commit();

             // Notification
            //  (new FundWalletNotificationService())->notify($transaction);

            // $msg = "Wallet ".$wallet->id ?? null." has been debited successfully for user ".$data['user_id'] ?? null;
            // info($msg);

            $msg = "Approved or completed successfully.";
            return $transaction;
        }catch(\Exception $e){
            $msg = $e->getMessage();
            return $this->error($msg, 400);
        }
    }

    /**
     * Check users wallet balance if amount is available
     */
    public function balanceCheck($amount, $userId = null){
        if ($amount <= 0) {
            return false;
        }

        $user = Auth::user();
        $id = $userId ?? $user->id;
        $wallet = Wallet::where('user_id', $id)->lockForUpdate()->first();
        if(!$wallet){
            $wallet = Wallet::create([
                'uuid' => uniqid(),
                'user_id' => $id,
                'is_active' => true,
            ]);
            $wallet->refresh();
        }

        if(($wallet->balance_after > 1 && $wallet->balance_after >= $amount)) {
            return true;
        }

        return false;
    }

    /**
     * Check users wallet balance if amount is available
     */
    public function userBalanceCheck($amount, $user_id){
        if ($amount <= 0) {
            return false;
        }

        $wallet = Wallet::where('user_id', $user_id)->lockForUpdate()->firstOrFail();

        if(($wallet->balance_after > 1 && $wallet->balance_after >= $amount)) {
            return true;
        }

        return false;
    }
}
