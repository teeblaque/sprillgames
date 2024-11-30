<?php

namespace App\Constants;

use Illuminate\Support\Facades\Storage;

class TransactionSource
{
    public const WALLET_TO_WALLET   = 'wallet_to_wallet';
    public const PAYSTACK = 'paystack';
    public const WALLET_TO_PLAN     = 'wallet_to_plan';
    public const WALLET_TO_PRODUCT     = 'wallet_to_product';
    public const PLAN_TO_WALLET     = 'plan_to_wallet';
    public const PLAN_EARNING_TO_WALLET     = 'plan_earning_to_wallet';
    public const PRODUCT_TO_WALLET     = 'product_to_wallet';
    public const LIQUIDATE_PRODUCT_TO_WALLET     = 'liquidate_product_to_wallet';
    public const PRODUCT_DIVIDEND_EARNING     = 'product_dividend_earning';
    public const TRANSFER_TO_WALLET = 'transfer_to_wallet'; // nuban
    public const WITHDRAW = 'withdraw';
    public const BANK_WITHDRAW = 'bank_withdraw';
    public const PAYMENT_LINK = 'payment_link';
    public const EARNING = 'earning';
    public const REVERSAL = 'reversal';
    public const PENALTY_FEE = 'penalty_fee';
    public const TRANSFER_TO_CARD = 'transfer_to_card';
    public const CARD_TO_WALLET = 'card_to_wallet';

    //GAMES ACTION
    public const SPECIAL_BET = 'special_bet';
    public const ONE_ON_ONE = 'one_on_one';
    public const PREDICT = 'predict';


    // Funding Source List
    public const WALLET       = 'wallet';
    public const NEW_CARD     = 'new_card';
    public const EXISTING_CARD = 'existing_card';

    public static function fundingSources()
    {
        return [
            self::WALLET,
            self::NEW_CARD,
            self::EXISTING_CARD,
        ];
    }

    public static function fundingSourcesList()
    {
        return [
            [
                'name' => 'Wallet',
                'value' => self::WALLET,
            ],
            [
                'name' => 'New Card',
                'value' => self::NEW_CARD,
            ],
            [
                'name' => 'Existing Card',
                'value' => self::EXISTING_CARD,
            ]
        ];
    }
}
