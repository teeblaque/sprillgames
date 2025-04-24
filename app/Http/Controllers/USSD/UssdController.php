<?php

namespace App\Http\Controllers\USSD;

use App\Constants\TransactionGroup;
use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\OneBet;
use App\Models\Predict;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserBank;
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

class UssdController extends Controller
{
    use ApiResponser;

    public function activate_subscription(Request $request, $msisdn)
    {
        try {
            $validator = Validator::make($request->input(), [
                'subscription_amount' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $user = User::where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);

            Subscription::updateOrCreate(
                ['user_id' => $user->id], [
                    'amount' => $request->amount,
                    'status' => 'active']);
                
            return $this->success('User subscription avtivated successfully', []);    
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function fund_wallet(Request $request, $msisdn)
    {
        try {
            $validator = Validator::make($request->input(), [
                'amount' => 'required'
            ]);

            $user = User::where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $payload = [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'gateway_response' => 'ussd',
                'payment_channel' => 'ussd',
                'ip_address' => null,
                'domain' => 'ussd',
                'narration' => 'Wallet funded from ussd'
            ];
            $logTransaction = (new WalletCredit())->createCredit($payload);

            return $this->success('Wallet funded successfully', []);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function subscription_status($msisdn)
    {
        try {
            $user = User::with('subscription')->where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);
            
            return $this->success('User status retrived', $user->subscription);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function wallet_balance($msisdn)
    {
        try {
            $user = User::with('wallet')->where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);
            
            return $this->success('User wallet retrived', $user->wallet);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function special(Request $request, $msisdn)
    {
        try {
            $success = false;
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:50|max:500000',
                'wallet_type' => 'required|in:real,bonus',
                'values' => 'required|array|min:2|max:2'
            ], [
                'amount.min' => 'The minimum amount you can stake is N50',
                'amount.max' => 'The maximum amount you can stake is N500,000.',
                'amount.numeric' => 'The amount must be a valid number.',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $user = User::where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);

            DB::beginTransaction();

            if (!(new AccountDebit)->balanceCheck($request->amount, $user)) {
                return $this->error("Insufficient wallet balance");
            }

            if ($request->amount < 50) return $this->error('Minimum amount to stake is N50');

            if ($request->amount > 500000) return $this->error('Maximum amount to stake is N500,000');

            //TODO: //generate 2 random numbers between 1 to 10
            $range = range(1, 10);
            // Shuffle the array to randomize the order
            shuffle($range);

            // Select the first two numbers
            $randomNumbers = array_slice($range, 0, 2);

            if (empty(array_diff($randomNumbers, $request->values)) && empty(array_diff($request->values, $randomNumbers))) {
                $success = true;
            } else {
                $success = false;
            }

            $reference = (new GenerateReferenceService())->generateReference();
            if (!$success) {
                $payload = [
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'amount' => $request->amount,
                    'trans_group' => TransactionGroup::SPECIAL_BET,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'narration' => 'Your special bet didnt go as planned and deducted from ' .$request->wallet_type,
                    'trx_source' => 'Wallet',
                    'wallet_type' => $request->wallet_type
                ];
                (new WalletDebit())->debit($payload);
            } else {
                $payload = [
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'amount' => $request->amount,
                    'trans_group' => TransactionGroup::SPECIAL_BET,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'ip_address' => null,
                    'domain' => null,
                    'narration' => 'Your special bet was successful and deducted from ' .$request->wallet_type,
                    'wallet_type' => $request->wallet_type
                ];
                (new WalletCredit())->createCredit($payload);
            }

            $bet = Bet::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'values' => $request->values,
                'amount_earned' => $request->amount * 5,
                'system_value' => $randomNumbers,
                'odds' => 5,
                'status' => $success ? 'successful' : 'failed'
            ]);

            DB::commit();
            return $this->success($success ? 'Bet was placed successfully' : 'You loss, better luck!!!', $bet, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 500);
        }
    }

    public function oneOnOne(Request $request, $msisdn)
    {
        try {
            $validator = Validator::make($request->all(), [
                'wallet_type' => 'required|in:real,bonus',
                'initial_value' => 'required|numeric|in:1,2,3',
                'amount' => 'required|numeric|min:50|max:500000',
            ], [
                'amount.min' => 'The minimum amount you can stake is N50',
                'amount.max' => 'The maximum amount you can stake is N500,000.',
                'amount.numeric' => 'The amount must be a valid number.',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $user = User::where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);

            DB::beginTransaction();

            if (!(new AccountDebit)->balanceCheck($request->amount, $user->id)) {
                return $this->error("Insufficient wallet balance");
            }

            $onebet = OneBet::create([
                'user_id' => $user->id,
                'initial_value' => $request->initial_value,
                'amount' => $request->amount,
                'amount_earned' => $request->amount * 2,
                'odds' => 2,
            ]);

            if ($onebet) {
                $reference = (new GenerateReferenceService())->generateReference();
                $payload = [
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'amount' => $request->amount,
                    'trans_group' => TransactionGroup::ONE_ON_ONE,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'narration' => 'Bet fee (ONE-ON-ONE)',
                    'trx_source' => 'Wallet',
                    'wallet_type' => $request->wallet_type
                ];
                (new WalletDebit())->debit($payload);

                DB::commit();
                return $this->success('Bet was placed successfully', [], 201);
            }

            return $this->error('Action was not successful, try again!!!', 400);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 500);
        }
    }

    public function joinBet(Request $request, $id, $msisdn)
    {
        try {
            $validator = Validator::make($request->all(), [
                'wallet_type' => 'required|in:real,bonus',
                'second_value' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $user = User::where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);

            DB::beginTransaction();
            $bet = OneBet::findOrFail($id);

            // Generate a random number between 1 and 2
            $randomNumber = rand(1, 3);
            if ($randomNumber == $bet->initial_value) {
                $payload = [
                    'user_id' => $bet->user_id,
                    'reference' => (new GenerateReferenceService())->generateReference(),
                    'amount' => $request->amount,
                    'trans_group' => TransactionGroup::ONE_ON_ONE,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'ip_address' => null,
                    'domain' => null,
                    'narration' => 'Your one-on-one bet was successful',
                ];
                (new WalletCredit())->createCredit($payload);

                $payload = [
                    'user_id' => $user->id,
                    'reference' => (new GenerateReferenceService())->generateReference(),
                    'amount' => $request->amount,
                    'trans_group' => TransactionGroup::ONE_ON_ONE,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'ip_address' => null,
                    'domain' => null,
                    'narration' => 'Your one on one bet was not successful',
                    'wallet_type' => $request->wallet_type
                ];
                (new WalletDebit())->debit($payload);

                $bet->update([
                    'winner' => $bet->user_id,
                    'status' => 'completed',
                    'second_value' => $request->second_value,
                    'result' => $randomNumber
                ]);
            } else if ($randomNumber == $request->second_value) {
                $payload = [
                    'user_id' => Auth::id(),
                    'reference' => (new GenerateReferenceService())->generateReference(),
                    'amount' => $request->amount,
                    'trans_group' => TransactionGroup::ONE_ON_ONE,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'ip_address' => null,
                    'domain' => null,
                    'narration' => 'wallet debitted for game',
                    'wallet_type' => $request->wallet_type
                ];
                (new WalletDebit())->debit($payload);

                $payload = [
                    'user_id' => Auth::id(),
                    'reference' => (new GenerateReferenceService())->generateReference(),
                    'amount' => $bet->amount_earned,
                    'trans_group' => TransactionGroup::ONE_ON_ONE,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'ip_address' => null,
                    'domain' => null,
                    'narration' => 'Your one-on-one bet was successful',
                ];
                (new WalletCredit())->createCredit($payload);

                $bet->update([
                    'winner' => $user->id,
                    'status' => 'completed',
                    'second_value' => $request->second_value,
                    'result' => $randomNumber
                ]);
            } else {
                $payload = [
                    'user_id' => $user->id,
                    'reference' => (new GenerateReferenceService())->generateReference(),
                    'amount' => $request->amount,
                    'trans_group' => TransactionGroup::ONE_ON_ONE,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'ip_address' => null,
                    'domain' => null,
                    'narration' => 'Your one on one bet was not successful',
                    'wallet_type' => $request->wallet_type
                ];
                (new WalletDebit())->debit($payload);

                $bet->update([
                    'winner' => null,
                    'status' => 'completed',
                    'second_value' => $request->second_value,
                    'result' => $randomNumber
                ]);
            }

            DB::commit();
            return $this->success('Action was successful', $bet, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 500);
        }
    }

    public function predict(Request $request, $msisdn)
    {
        try {
            $validator = Validator::make($request->all(), [
                'wallet_type' => 'required|in:real,bonus',
                'values' => 'required|array',
                'amount' => 'required|numeric|min:50|max:500000',
            ], [
                'amount.min' => 'The minimum amount you can stake is N50',
                'amount.max' => 'The maximum amount you can stake is N500,000.',
                'amount.numeric' => 'The amount must be a valid number.',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $user = User::where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);

            DB::beginTransaction();

            if (!(new AccountDebit)->balanceCheck($request->amount, $user->id)) {
                return $this->error("Insufficient wallet balance");
            }

            $predict = Predict::create([
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'amount_earned' => $request->amount * 20,
                'odds' => 20,
                'values' => $request->values,
            ]);

            if ($predict) {
                $reference = (new GenerateReferenceService())->generateReference();
                $payload = [
                    'user_id' => Auth::id(),
                    'reference' => $reference,
                    'amount' => $request->amount,
                    'trans_group' => TransactionGroup::PREDICT,
                    'gateway_response' => 'wallet',
                    'payment_channel' => 'wallet',
                    'narration' => 'Bet fee (PREDICT & WIN)',
                    'trx_source' => 'Wallet',
                    'wallet_type' => $request->wallet_type
                ];
                (new WalletDebit())->debit($payload);

                DB::commit();
                return $this->success('Bet was placed successfully', $predict, 201);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function pendingBet($msisdn)
    {
        try {
            $user = User::where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);

            $bet = Predict::where(['user_id' => $user->id, 'status' => 'pending'])->get();
            return $this->success('Record retrieved', $bet);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function store(Request $request, $msisdn)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'user_bank_id' => 'nullable|exists:user_banks,id',
                'amount' => 'required',
                'remark' => 'nullable',
                'transaction_pin' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $user = User::where(['phone' => $msisdn])->first();
            if(!$user) return $this->error('user with msisdn not found', 404);

            if (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return $this->error('Invalid transaction pin.', 400);
            }
            if ($request->amount < 100) {
                return $this->error('Minimum withdrawal amount must be greater than N100', 400);
            }

            if (!(new AccountDebit)->balanceCheck($request->amount, $user->id)) {
                return $this->error("Insufficient wallet balance");
            }

            $checkWithdrawal = Withdrawal::where(['user_id' => $user->id, 'status' => 'pending'])->first();
            if ($checkWithdrawal) return $this->error('You have a pending withdrawal request, kindly cancel to initiate a new one', 400);

            $reference = (new GenerateReferenceService())->generateReference();
            $payload = [
                'user_id' => $user->id,
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

            $bank = UserBank::where('user_id', $user->id)->first();
            if (!$bank) {
                return $this->error('Kindly set a payout account', 400);
            }

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'user_bank_id' => $bank->id,
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

    public function user_bank($msisdn)
    {
        $user = User::where(['phone' => $msisdn])->first();
        if(!$user) return $this->error('user with msisdn not found', 404);

        $bank = UserBank::where('user_id', $user->id)->first();
        if(!$bank) return $this->error('User bank account not created', 404);

        return $this->success('Bank Info retrieved successfully', $bank);
    }
}
