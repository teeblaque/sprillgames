<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Paystack extends Model
{
    use HasFactory;

    protected $baseUrl;
    protected $env;
    protected $secretKey;
    protected $publicKey;

    public function __construct()
    {
        $this->baseUrl = env('PAYSTACK_PAYMENT_URL');
        $this->env = env('PAYSTACK_ENV');
        if ($this->env == "test") {
            $this->secretKey = env('PAYSTACK_TEST_SECRET_KEY');
            $this->publicKey = env('PAYSTACK_TEST_PUBLIC_KEY');
        } else {
            $this->secretKey = env('PAYSTACK_LIVE_SECRET_KEY');
            $this->publicKey = env('PAYSTACK_LIVE_PUBLIC_KEY');
        }
    }

    public function verifyTransaction($txRef)
    {
        $response = Http::withToken($this->secretKey)
            ->asJson()
            ->get($this->baseUrl . "/transaction/verify/$txRef");

        return json_decode($response->body());
    }

    public function chargeAuthorization($email, $amount, $authRef)
    {
        $response = Http::withToken($this->secretKey)
            ->asForm()
            ->post($this->baseUrl . "/transaction/charge_authorization", [
                'email' => $email,
                'amount' => $amount * 100,
                'authorization_code' => $authRef,
            ]);

        return json_decode($response->body());
    }

    public function resolveAccountNumber($bank, $account)
    {
        $response = Http::withToken($this->secretKey)
            ->asJson()
            ->get($this->baseUrl . "/bank/resolve?account_number=$account&bank_code=$bank");

        return json_decode($response->body());
    }

    public function createTransferRecipient(UserBank $bank)
    {
        $response = Http::withToken($this->secretKey)
            ->asForm()
            ->post($this->baseUrl . "/transferrecipient", [
                'type' => "nuban",
                'currency' => "NGN",
                'name' => $bank->account_name,
                'account_number' => $bank->account_number,
                'bank_code' => $bank->bank_code,
            ]);

        $res = json_decode($response->body());

        if ($res->status) {
            $bank->update([
                'transfer_recipient' => $res->data->recipient_code,
            ]);
            return true;
        }

        return false;
    }

    public function createTransfer(UserBank $bank, $amount)
    {
        $response = Http::withToken($this->secretKey)
            ->asForm()
            ->post($this->baseUrl . "/transfer", [
                'source' => "balance",
                'amount' => $amount * 100,
                'recipient' => $bank->transfer_recipient,
                'reason' => 'Withdrawal'
            ]);

            return json_decode($response->body());
    }

    public function createVirtualAccount($customer)
    {
        $response = Http::withToken($this->secretKey)
        ->asForm()
        ->post($this->baseUrl . "/dedicated_account", [
            'customer' => $customer,
            'preferred_bank' => config('app.env') == 'production' ? 'wema-bank' : 'test-bank',
        ]);

    return json_decode($response->body());
    }

    public function createCustomer($param)
    {
        $response = Http::withToken($this->secretKey)
            ->asForm()
            ->post($this->baseUrl . "/customer", [
                'email' => $param['email'],
                'first_name' => $param['first_name'],
                'last_name' => $param['last_name'],
                'phone' => $param['phone'],
            ]);

        return json_decode($response->body());
    }

    public function createPlan($name, $interval, $amount)
    {
        $response = Http::withToken($this->secretKey)
            ->asForm()
            ->post($this->baseUrl . "/plan", [
                'name' => $name,
                'interval' => $interval = 'yearly' ? 'annually' : $interval,
                'amount' => $amount * 100,
            ]);

        return json_decode($response->body());
    }

    public function createSubscription($customer, $plan)
    {
        $response = Http::withToken($this->secretKey)
            ->asForm()
            ->post($this->baseUrl . "/subscription", [
                'customer' => $customer,
                'plan' => $plan,
            ]);

        return json_decode($response->body());
    }

    public function bankList()
    {
        $response = Http::withToken($this->secretKey)
            ->asJson()
            ->get($this->baseUrl . "/bank?country=nigeria");

        return json_decode($response->body());
    }

    public function resolveAccount($account_number, $bank_code)
    {
        $response = Http::withToken($this->secretKey)
            ->asJson()
            ->get($this->baseUrl . "/bank/resolve?account_number=".$account_number."&bank_code=".$bank_code);

        return json_decode($response->body());
    }
}
