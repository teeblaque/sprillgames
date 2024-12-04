<?php
namespace App\Services\BankAccount;

use App\Http\Controllers\BankOne\CoreTransactionsService;
use App\Models\Appzone\BankOneAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\GenerateReferenceService;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountDebit {
    use ApiResponser;

    /**
     * Check users account balance if amount is available
     */
    public function balanceCheck($amount, $userId = null){
        if ($amount <= 0) {
            return false;
        }

        $user = auth()->user();
        $id = $userId ?? $user->id;
        $wallet = Wallet::where('user_id', $id)->lockForUpdate()->first();
        
        if(!$wallet){
            $wallet = Wallet::create([
                'uuid' => uniqid(),
                'user_id' => $id,
                'is_active' => true,
            ]);
            $wallet->refresh();
        }

        if($wallet->balance_after > 1 && $wallet->balance_after >= $amount) {
            return true;
        }

        return false;
    }

    /**
     * Check users wallet balance if amount is available
     */
    public function userBalanceCheck($amount, $user_id){
        if ($amount <= 0) {
            return false;
        }
        $user = User::where('id', $user_id)->firstOrFail();
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

        if(!$wallet){
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'is_active' => true,
            ]);
            $wallet->refresh();
        }

        return false;
    }
}
?>
