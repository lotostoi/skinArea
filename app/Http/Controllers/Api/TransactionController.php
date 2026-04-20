<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
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

        $query = Transaction::query()->where('user_id', $user->id);

        $statusParam = $request->query('status');
        if (is_string($statusParam) && $statusParam !== '') {
            $status = TransactionStatus::tryFrom($statusParam);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        $balanceTypeParam = $request->query('balance_type');
        if (is_string($balanceTypeParam) && $balanceTypeParam !== '') {
            $balanceType = BalanceType::tryFrom($balanceTypeParam);
            if ($balanceType !== null) {
                $query->where('balance_type', $balanceType);
            }
        }

        $typeParam = $request->query('type');
        if (is_string($typeParam) && $typeParam !== '') {
            $type = TransactionType::tryFrom($typeParam);
            if ($type !== null) {
                $query->where('type', $type);
            }
        }

        $transactions = $query
            ->latest('created_at')
            ->paginate(perPage: (int) $request->query('per_page', 20))
            ->withQueryString();

        return TransactionResource::collection($transactions)->response();
    }
}
