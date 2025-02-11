<?php

namespace App\Http\Controllers;

use App\Constants\TransactionSource;
use App\Models\SiruWebhook;
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
            $webhook = SiruWebhook::create([
                'event' => $request['event'],
                'result' => json_encode($request['payment'])
            ]);

            $transaction = Transaction::where(['reference' => $request['payment']['reference']])->first();
            if (!$transaction) return;

            if ($transaction && $transaction->status == 'confirmed') return;

            if ($request['event'] == 'cancel') {
                $transaction->update([
                    'status' => $request['payment']['marking']
                ]);
            } else if ($request['event'] == 'confirm') {
                $transaction->update([
                    'status' => $request['payment']['marking']
                ]);

                $payload = [
                    'user_id' => $transaction->user_id,
                    'reference' => $request['payment']['reference'],
                    'amount' => $request['payment']['amount']['amount'] / 100,
                    'gateway_response' => 'siru',
                    'payment_channel' => 'siru channel',
                    'ip_address' => null,
                    'domain' => 'siru',
                    'trx_source' => TransactionSource::SIRU,
                    'narration' => 'Wallet funded from Siru'
                ];
                (new WalletCredit())->createCredit($payload);
            }
            return $this->success('Payment successful');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
