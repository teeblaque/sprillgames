<?php

namespace App\Http\Controllers;

use App\Models\Paystack;
use App\Models\UserBank;
use App\Models\UserCard;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BankController extends Controller
{
    use ApiResponser;

    public function store(Request $request) 
    {
        try {
            $validator = Validator::make($request->input(), [
                'bank_code' => 'required',
                'account_number' => 'required',
                'account_name' => 'required',
                'bank_name' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $checkBankExist = UserBank::where('user_id', Auth::id())->first();
            if ($checkBankExist) {
                return $this->error('You cannot add more than one payout method', 400);
            }

            DB::beginTransaction();
            $paystack = new Paystack();

            $bank = UserBank::create([
                'user_id' => Auth::id(),
                'provider' => 'commercial',
                'bank_code' => $request->bank_code,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'bank_name' => $request->bank_name,
                'transfer_recipient' => 'no code'
            ]);
            if ($bank) {
                $tx = $paystack->createTransferRecipient($bank);
            }
            DB::commit();
            return $this->success('Bank account saved successfully', $bank, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 500);
        }
    }

    public function index()
    {
        $banks = UserBank::where(['user_id' => Auth::id(), 'provider' => 'commercial'])->first();
        return $this->success('Bank retrieved', $banks);
    }
    
    public function removeBank($id)
    {
        try {
            $bank = UserBank::where(['id' => $id, 'user_id' => Auth::id()])->first();
            if ($bank && $bank->delete()) {
                return $this->success('Bank Account removed successfully', [], 200);
            }else{
                return $this->error('Invalid bank Id', 400);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function removeCard($id)
    {
        try {
            $bank = UserCard::where(['id' => $id, 'user_id' => Auth::id()])->first();
            if ($bank && $bank->delete()) {
                return $this->success('User card removed successfully', [], 200);
            }else{
                return $this->error('Invalid bank Id', 400);
            }
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
