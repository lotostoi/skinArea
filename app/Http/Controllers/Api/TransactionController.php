<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionIndexRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function index(TransactionIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $query = Transaction::query()->where('user_id', $user->id);

        $statusParam = $validated['status'] ?? null;
        if (is_string($statusParam) && $statusParam !== '') {
            $status = TransactionStatus::tryFrom($statusParam);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        $balanceTypeParam = $validated['balance_type'] ?? null;
        if (is_string($balanceTypeParam) && $balanceTypeParam !== '') {
            $balanceType = BalanceType::tryFrom($balanceTypeParam);
            if ($balanceType !== null) {
                $query->where('balance_type', $balanceType);
            }
        }

        $typeParam = $validated['type'] ?? null;
        if (is_string($typeParam) && $typeParam !== '') {
            $type = TransactionType::tryFrom($typeParam);
            if ($type !== null) {
                $query->where('type', $type);
            }
        }

        $transactions = $query
            ->latest('created_at')
            ->paginate(perPage: (int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return TransactionResource::collection($transactions)->response();
    }
}
