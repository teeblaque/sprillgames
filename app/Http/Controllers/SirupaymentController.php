<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\SprillGamesService\SiruService;
use App\Traits\ApiResponser;
use App\Traits\HasPhoneFieldTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SirupaymentController extends Controller
{
    use ApiResponser, HasPhoneFieldTrait;

    public function initiate(Request $request) 
    {
        try {
            $validator = Validator::make($request->all(), [
                'reference' => 'required|exists:transactions,reference',
                'amount' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 400);
            }

            $transaction = Transaction::where('reference', $request->reference)->first();
            if (!$transaction || $transaction->status != 'pending') {
                return $this->success('Record not found', 400);
            }

            $params = [
                'reference' => $request->reference,
                'country' => 'NG',
                'redirectUrl' => 'https://google.com',
                'notifyUrl' => 'https://webhook.site/7e47a698-584a-4e10-bbf8-b24292d3f5bd',
                'amount' => [
                    'amount' => $request->amount * 100,
                    'currency' => 'NGN'
                ],
                'customer' => [
                    'phoneNumber' => $this->getPhoneNumberWithDialingCode(Auth::user()->phone, '+234'),
                    'email' => Auth::user()->email,
                    'firstName' => Auth::user()->name
                ],
            ];
            $siruPayment = $siruPayment = (new SiruService())->SiruPost('/payments', $params);
            $transaction->update([
                'status' => $siruPayment->marking,
                'provider_uuid' => $siruPayment->uuid
            ]);
            
            return $this->success('Payment was successful', $siruPayment, 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function confirm($reference)
    {
        try {
            $transaction = Transaction::where('reference', $reference)->first();
            if (!$transaction) return $this->error('Transaction with reference not found', 400);

            $siruPayment = (new SiruService())->siruGet('/payments/'. $transaction->provider_uuid);
            $transaction->update([
                'status' => $siruPayment->marking
            ]);

            return $this->success('Record retrieved', $siruPayment);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
