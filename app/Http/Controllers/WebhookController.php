<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\WalletCredit;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    use ApiResponser;

    public function confirm_payment(Request $request)
    {
        try {
            $transaction = Transaction::where('reference', $request['payment']['reference'])->first();
            if (!$transaction) return;

            if ($request['event'] == 'cancel') {
                $transaction->update([
                    'status' => $request['payment']['marking']
                ]);
            }else if($request['event'] == 'confirm') {
                $transaction->update([
                    'status' => $request['payment']['marking']
                ]);

                $payload = [
                    'user_id' => $transaction->user_id,
                    'reference' => $request['payment']['marking'],
                    'amount' => $request['payment']['amount']['amount']/100,
                    'gateway_response' => 'siru',
                    'payment_channel' => 'siru channel',
                    'ip_address' => null,
                    'domain' => 'siru',
                    'narration' => 'Wallet funded from Siru'
                ];
                (new WalletCredit())->createCredit($payload);
            }
            return 'Done';
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
