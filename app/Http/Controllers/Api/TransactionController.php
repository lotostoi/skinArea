<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->paginate(perPage: (int) $request->query('per_page', 20))
            ->withQueryString();

        return TransactionResource::collection($transactions)->response();
    }
}
