<?php

namespace App\Services;

use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Models\WalletTransaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponser;
use App\Services\GenerateReferenceService;
use App\Constants\TransactionSource;
use App\Models\Webhook;
use App\Services\Zanibal\AddFundToCashAccountService;
use App\Services\Notification\FundWalletNotificationService;

class WalletCredit
{

    use ApiResponser;
    /**
     * Credit user wallet.
     */
    public function credit($data){
        try{
            DB::beginTransaction();
            if ($data['amount'] <= 0) {
                return $this->error('Invalid amount', 400);
            }
            
            $transaction = WalletTransaction::where('trx_reference', $data['reference'])->firstOrFail();
            $wallet = Wallet::where('id', $transaction->wallet_id)->lockForUpdate()->firstOrFail();
            if($transaction->is_active){
                $msg = 'Transaction has already been validated.';
                return $this->error($msg, 400);
            }
            $balance_before = (float)($wallet->balance_after);
            $balance_after = (float)($wallet->balance_after) + (float)($data['amount']);

            if($transaction){
                $transaction->trx_type = TransactionType::CREDIT;
                $transaction->trx_status = TransactionStatus::SUCCCES;
                $transaction->trx_source = TransactionSource::PAYSTACK;
                $transaction->gateway_response = $data['gateway_response'] ?? null;
                $transaction->payment_channel = $data['channel'] ?? null;
                $transaction->amount = $data['amount'];
                $transaction->balance_before = $balance_before;
                $transaction->balance_after =  $balance_after;
                $transaction->ip_address = $data['ip_address'] ?? null;
                $transaction->domain = $data['domain'] ?? null;
                $transaction->is_active = true;
                $transaction->save();

                // Notification
                // (new FundWalletNotificationService())->notify($transaction);
            }
            // update balance_before and balance_after for wallet
            $wallet->balance_before  =  $balance_before;
            $wallet->balance_after = $wallet->balance_after + (float)($data['amount']);
            $wallet->save();

            DB::commit();

            $msg = "Transaction has been validated successfully.";
            return $wallet;
            return $this->success($wallet, $msg);

        }catch(\Exception $e){
            $msg = $e->getMessage();
            return $this->error($msg, 400);
        }
    }

    /**
     * Credit user wallet.
     */
    public function createCredit($data){
        try{
            DB::beginTransaction();

            if ($data['amount'] <= 0) {
                return $this->error('Invalid amount', 400);
            }

            $transaction = WalletTransaction::where('trx_reference', $data['reference'])->first();
            if($transaction){
                $msg = 'Transaction has already been validated.';
                return $this->error($msg, 400);
            }
            $reference = ($data['reference']) ??  (new GenerateReferenceService())->generateReference();
            $wallet = Wallet::where('user_id', $data['user_id'])->firstOrFail();
            
            $balance_before = (float)($wallet->balance_after);
            $balance_after = (float)($balance_before) + (float)($data['amount']);

            // update balance_before and balance_after for wallet
            $wallet->balance_before  =  $balance_before;
            $wallet->balance_after += (float)($data['amount']);
            $wallet->save();

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' =>   $data['user_id'],
                'trx_reference' =>  $reference,
                'trx_type' => TransactionType::CREDIT,
                'trx_status' => TransactionStatus::SUCCCES,
                'gateway_response' => $data['gateway_response'] ?? null,
                'payment_channel' => $data['payment_channel'] ?? null,
                'trx_source' =>  $data['trx_source'] ?? TransactionSource::PAYSTACK,
                'amount' =>  $data['amount'], // data must have
                'balance_before' =>  $balance_before,
                'balance_after' =>   $balance_after,
                'ip_address' =>  $data['ip_address'] ?? null,
                'domain' =>   $data['domain'] ?? null,
                'narration' =>   $data['narration'] ?? null,
                'trans_group' => $data['trans_group'] ?? null,
                'is_active' =>  true,
            ]);

            DB::commit();

            // Notification
            // (new FundWalletNotificationService())->notify($transaction);

            $msg = "Approved or completed successfully.";
            return $transaction;
            // return $this->success($transaction, $msg);

        }catch(\Exception $e){
            $msg = $e->getMessage();
            DB::rollBack();
            return $this->error($msg, 400);
        }
    }

    /**
     * Credit user wallet.
     */
    public function initiateCredit($data){
        try{
            DB::beginTransaction();

            if ($data['amount'] <= 0) {
                return $this->error('Invalid amount', 400);
            }

            $reference = ($data['reference']) ??  (new GenerateReferenceService())->generateReference();
            $wallet = Wallet::where('user_id', $data['user_id'])->firstOrFail();

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_plan_id' => $data['user_plan_id'] ?? null,
                'user_product_id' => $data['user_product_id'] ?? null,
                'trx_reference' =>  $reference,
                'trx_type' => TransactionType::CREDIT,
                'trx_status' => TransactionStatus::PENDING,
                'trx_source' => $data['trx_source'], // data must have
                'amount' =>  $data['amount'], // data must have
            ]);

            DB::commit();

            $msg = "Payment Initiated.";
            return $transaction;
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            return $this->error($msg, 400);
        }

    }

    public function transferCredit($data){
        try{
            $transaction = "Successful.";
            return $transaction;
        }catch(\Exception $e){
            $msg = $e->getMessage();
            return $this->error($msg, 400);
        }
    }

}
