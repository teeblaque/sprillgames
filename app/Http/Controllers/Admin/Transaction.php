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
        if ($request->user_id && $request->search) {
            $transactions = WalletTransaction::with(['user', 'wallet'])->where('user_id', $request->user_id)->orWhere('')->paginate($request->per_page ?? 20);
        } elseif ($request->user_id) {
            $transactions = WalletTransaction::with(['user', 'wallet'])->where('user_id', $request->user_id)->paginate($request->per_page ?? 20);
        } else {
            $transactions = WalletTransaction::with(['user', 'wallet'])->paginate($request->per_page ?? 20);
        }
        return $this->success('Action successful', $transactions);
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
