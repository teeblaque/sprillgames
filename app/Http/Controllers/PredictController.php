<?php

namespace App\Http\Controllers;

use App\Constants\TransactionGroup;
use App\Models\Fixtures;
use App\Models\Predict;
use App\Services\BankAccount\AccountDebit;
use App\Services\GenerateReferenceService;
use App\Services\SprillGamesService\SportService;
use App\Services\WalletDebit;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PredictController extends Controller
{
    use ApiResponser;

    public function predictMach()
    {
        try {
            $fixturesArray = [];
            $fixture = Fixtures::whereDate('created_at', Carbon::today())->first();
            if (!$fixture) {
                $matches = (new SportService())->getNext5Fixtures(Carbon::now()->format('Y-m-d'));
                foreach ($matches->response as $value) {
                    if (count($fixturesArray) >= 5) {
                        break; // Stop the loop when the array length reaches 5
                    }
                    $fix = [
                        'fixture_id' => $value->fixture->id,
                        'home_team' => [
                            'name' => $value->teams->home->name,
                            'logo' => $value->teams->home->logo
                        ],
                        'away_team' => [
                            'name' => $value->teams->away->name,
                            'logo' => $value->teams->away->logo
                        ],
                        'date' => $value->fixture->date,
                    ];
                    array_push($fixturesArray, $fix);
                }
                $fixture = Fixtures::create([
                    'matches' => $fixturesArray
                ]);
            }
            return $this->success('Fixtures retrieved', $fixture->matches, 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function predict(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
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

            DB::beginTransaction();

            if (!(new AccountDebit)->balanceCheck($request->amount, Auth::id())) {
                return $this->error("Insufficient wallet balance");
            }

            $predict = Predict::create([
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'amount_earned' => $request->amount * 200,
                'odds' => 200,
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
                    'trx_source' => 'Wallet'
                ];
                (new WalletDebit())->debit($payload);

                DB::commit();
                return $this->success('Bet was placed successfully', $predict, 201);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function pendingBet()
    {
        try {
            $bet = Predict::where(['user_id' => Auth::id(), 'status' => 'pending'])->get();
            return $this->success('Record retrieved', $bet);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
