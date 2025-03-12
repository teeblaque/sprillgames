<?php

namespace App\Http\Controllers;

use App\Models\Paystack;
use App\Models\Transaction;
use App\Models\UserCard;
use App\Services\GenerateReferenceService;
use App\Services\WalletCredit;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddFundController extends Controller
{
    use ApiResponser;

    public function verifyPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'reference' => 'required|exists:transactions,reference',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }


            $checkTransaction = Transaction::where('reference', $request->reference)->first();
            if(!$checkTransaction) return $this->error('Invalid payment reference', 400);

            $paystack = new Paystack;
            $tx = $paystack->verifyTransaction($request->reference);
            if ($tx->status) {
                $paymentData = $tx->data;
                $paymentStatus = $paymentData->status;
                $checkTransaction->update([
                    'status' => $paymentData->status
                ]);
                if ($paymentStatus == 'success') {
                    //TODO: update wallet and log transaction
                    $payload = [
                        'user_id' => Auth::id(),
                        'reference' => $paymentData->reference,
                        'amount' => $checkTransaction->amount,
                        // $paymentData->amount,
                        'gateway_response' => $paymentData->gateway_response,
                        'payment_channel' => $paymentData->channel,
                        'ip_address' => $paymentData->ip_address,
                        'domain' => $paymentData->domain,
                        'narration' => 'Wallet funded'
                    ];
                    $logTransaction = (new WalletCredit())->createCredit($payload);

                    $userCard = UserCard::where('authorization_code', $paymentData->authorization->authorization_code)->first();
                    if (!$userCard) {
                        UserCard::create([
                            'user_id' => Auth::id(),
                            'authorization_code' => $paymentData->authorization->authorization_code,
                            'bank' => $paymentData->authorization->bank,
                            'brand' => $paymentData->authorization->brand,
                            'last4' => $paymentData->authorization->last4,
                            'bin' => $paymentData->authorization->bin,
                            'exp_month' => $paymentData->authorization->exp_month,
                            'exp_year' => $paymentData->authorization->exp_year,
                        ]);
                    }
                    return $this->success('Payment approved successfully', $logTransaction);
                } else {
                    return $this->error($tx->message, 400);
                }

            } else {
                return $this->error($tx->message, 400);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function authorizedCard(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'amount' => 'required',
                'card_id' => 'required|exists:user_cards,id'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $refernce = (new GenerateReferenceService())->generateReference();
            $checkReference = Transaction::where('reference', $refernce)->first();
            if($checkReference) return $this->error('Transaction reference could not be generated, try again!!!', 400);

            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'reference' => $refernce,
                'amount' => $request->amount,
                'status' => 'pending',
                'provider' => 'paystack'
            ]);

            $card = UserCard::where(['id' => $request->card_id, 'user_id' => Auth::id()])->first();
            if (!$card) return $this->error('No card found', 400);

            $paystack = new Paystack;
            $tx = $paystack->chargeAuthorization(Auth::user()->email, $request->amount, $card->authorization_code);
            if ($tx->status) {
                $paymentData = $tx->data;
                $paymentStatus = $paymentData->status;
                $transaction->update([
                    'status' => $paymentData->status
                ]);
                if ($paymentStatus == 'success') {
                    $payload = [
                        'user_id' => Auth::id(),
                        'reference' => $paymentData->reference,
                        'amount' => $paymentData->amount/100,
                        'gateway_response' => $paymentData->gateway_response,
                        'payment_channel' => $paymentData->channel,
                        'ip_address' => $paymentData->ip_address,
                        'domain' => $paymentData->domain,
                        'narration' => 'Wallet funded'
                    ];
                    $logTransaction = (new WalletCredit())->createCredit($payload);

                    return $this->success('Payment approved successfully', $logTransaction);
                } else {
                    return $this->error($tx->message, 400);
                }

            } else {
                return $this->error($tx->message, 400);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function resolveAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'account_number' => 'required',
                'bank_code' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $banks = new Paystack;
            $tx = $banks->resolveAccount($request->account_number, $request->bank_code);
            if ($tx->status) {
                return $this->success($tx->message, $tx->data);
            }else{
                return $this->error($tx->message, 400);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function banks()
    {
        try {
            $banks = new Paystack;
            $tx = $banks->bankList();
            if ($tx->status) {
                return $this->success($tx->message, $tx->data);
            }else{
                return $this->error($tx->message, 400);
            }
            
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function cards()
    {
        try {
            $cards = UserCard::where('user_id', Auth::id())->get();
            return $this->success('Card record retrieved successfully', $cards, 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function initiateTransaction(Request $request) 
    {
        try {
            $validator = Validator::make($request->input(), [
                'amount' => 'required',
                'provider' => 'required|in:paystack,siru'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $refernce = (new GenerateReferenceService())->generateReference();
            $checkReference = Transaction::where('reference', $refernce)->first();
            if($checkReference) return $this->error('Transaction reference could not be generated, try again!!!', 400);

            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'reference' => $refernce,
                'amount' => $request->amount,
                'status' => 'pending',
                'provider' => $request->provider
            ]);
            if ($transaction) {
                return $this->success('Reference generated', $transaction, 201);
            }else{
                return $this->error('Reference could not be generated, try again', 400);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
