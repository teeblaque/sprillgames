<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\OneBet;
use App\Models\Predict;
use App\Models\WalletTransaction;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class Transaction extends Controller
{
    use ApiResponser;

    public function index(Request $request)
    {
        $query = WalletTransaction::with(['user', 'wallet']);
    
        // Filter by user ID
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
    
        // Filter by transaction type
        if ($request->transaction_type) {
            $query->where('trx_type', $request->transaction_type);
        }
    
        // Filter by date range (if both start_date and end_date are provided)
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }
    
        // Search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('trx_reference', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function ($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
            });
        }
    
        // Get transactions
        $transactions = $query->paginate($request->per_page ?? 20);
    
        // Calculate totals
        $totalCredit = $query->where('trx_type', 'credit')->sum('amount');
        $totalDebit = $query->where('trx_type', 'debit')->sum('amount');
        $profit = $totalCredit - $totalDebit;
    
        // Build response
        $result = [
            'profit_overview' => [
                'total_credit' => $totalCredit,
                'total_debit' => $totalDebit,
                'profit' => $profit,
            ],
            'transactions' => $transactions
        ];
    
        return $this->success('Action successful', $result);
    }
    

    public function special_bet(Request $request)
    {
        $query = Bet::with(['user']);

        // Filter by user_id if provided
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Apply search filter if a search request is passed
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('uuid', 'ILIKE', "%{$request->search}%")
                    ->orWhere('status', 'ILIKE', "%{$request->search}%")
                    ->orWhereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'ILIKE', "%{$request->search}%")
                            ->orWhere('email', 'ILIKE', "%{$request->search}%");
                    });
            });
        }

        $transactions = $query->paginate($request->per_page ?? 20);

        return $this->success('Action successful', $transactions);
    }


    public function one_on_one(Request $request)
    {
        // if ($request->user_id) {
        //     $transactions = OneBet::with(['user'])->where('user_id', $request->user_id)->paginate($request->per_page ?? 20);
        // } else {
        //     $transactions = OneBet::with(['user'])->paginate($request->per_page ?? 20);
        // }
        // return $this->success('Action successful', $transactions);
        $query = OneBet::with(['user']);

        // Filter by user_id if provided
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Apply search filter if a search request is passed
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('uuid', 'ILIKE', "%{$request->search}%")
                    ->orWhere('status', 'ILIKE', "%{$request->search}%")
                    ->orWhereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'ILIKE', "%{$request->search}%")
                            ->orWhere('email', 'ILIKE', "%{$request->search}%");
                    });
            });
        }

        $transactions = $query->paginate($request->per_page ?? 20);

        return $this->success('Action successful', $transactions);
    }

    public function predict(Request $request)
    {
        // if ($request->user_id) {
        //     $transactions = Predict::with(['user'])->where('user_id', $request->user_id)->paginate($request->per_page ?? 20);
        // } else {
        //     $transactions = Predict::with(['user'])->paginate($request->per_page ?? 20);
        // }
        // return $this->success('Action successful', $transactions);

        $query = Predict::with(['user']);

        // Filter by user_id if provided
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Apply search filter if a search request is passed
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('uuid', 'ILIKE', "%{$request->search}%")
                    ->orWhere('status', 'ILIKE', "%{$request->search}%")
                    ->orWhereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'ILIKE', "%{$request->search}%")
                            ->orWhere('email', 'ILIKE', "%{$request->search}%");
                    });
            });
        }

        $transactions = $query->paginate($request->per_page ?? 20);

        return $this->success('Action successful', $transactions);
    }
}
