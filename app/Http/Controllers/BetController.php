<?php

namespace App\Http\Controllers;

use App\Constants\TransactionGroup;
use App\Models\Bet;
use App\Models\Fixtures;
use App\Models\OneBet;
use App\Models\Predict;
use App\Models\WalletTransaction;
use App\Services\BankAccount\AccountDebit;
use App\Services\GenerateReferenceService;
use App\Services\SprillGamesService\SportService;
use App\Services\WalletCredit;
use App\Services\WalletDebit;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BetController extends Controller
{
    use ApiResponser;

    public function oneBets(Request $request)
    {
        $query = OneBet::where('status', 'pending')
            // ->where('user_id', Auth::id())
            ->with('user');

        // Apply search filter if a search request is passed
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('uuid', 'ILIKE', "%{$request->search}%")
                    ->orWhere('status', 'ILIKE', "%{$request->search}%")
                    ->orWhere('amount', 'ILIKE', "%{$request->search}%");
            });
        }

        $bets = $query->paginate($request->per_page ?? 20);

        return $this->success('Record retrieved', $bets);
    }

    public function special(Request $request)
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
            DB::beginTransaction();

            if (!(new AccountDebit)->balanceCheck($request->amount, Auth::id())) {
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
                    'user_id' => Auth::id(),
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
                    'user_id' => Auth::id(),
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
                'user_id' => Auth::id(),
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

    public function oneOnOne(Request $request)
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
            DB::beginTransaction();

            if (!(new AccountDebit)->balanceCheck($request->amount, Auth::id())) {
                return $this->error("Insufficient wallet balance");
            }

            $onebet = OneBet::create([
                'user_id' => Auth::id(),
                'initial_value' => $request->initial_value,
                'amount' => $request->amount,
                'amount_earned' => $request->amount * 2,
                'odds' => 2,
            ]);

            if ($onebet) {
                $reference = (new GenerateReferenceService())->generateReference();
                $payload = [
                    'user_id' => Auth::id(),
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

    public function joinBet(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'wallet_type' => 'required|in:real,bonus',
                'second_value' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }
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
                    'user_id' => Auth::id(),
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
                    'winner' => Auth::id(),
                    'status' => 'completed',
                    'second_value' => $request->second_value,
                    'result' => $randomNumber
                ]);
            } else {
                $payload = [
                    'user_id' => Auth::id(),
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

    public function transactions(Request $request)
    {
        if (!$request->bet_type) {
            return $this->error('Bet type is required');
        }

        $query = WalletTransaction::where([
            'user_id' => Auth::id(),
            'trans_group' => $request->bet_type,
        ]);

        // Apply search filter if a search request is passed
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('trx_reference', 'ILIKE', "%{$request->search}%")
                    ->orWhere('trx_source', 'ILIKE', "%{$request->search}%")
                    ->orWhere('created_at', 'ILIKE', "%{$request->search}%")
                    ->orWhere('gateway_response', 'ILIKE', "%{$request->search}%");
            });
        }

        $transactions = $query->paginate($request->per_page ?? 20);

        return $this->success('Record retrieved', $transactions);
    }
}
