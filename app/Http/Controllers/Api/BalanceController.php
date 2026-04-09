<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BalanceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DepositBalanceRequest;
use App\Http\Requests\DepositCallbackRequest;
use App\Http\Requests\WithdrawBalanceRequest;
use App\Http\Resources\BalanceResource;
use App\Models\Balance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        foreach ([BalanceType::Main, BalanceType::Hold] as $type) {
            Balance::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $type,
                ],
                ['amount' => 0],
            );
        }

        $user->load('balances');

        return BalanceResource::collection($user->balances)->response();
    }

    public function deposit(DepositBalanceRequest $request): JsonResponse
    {
        $request->validated();

        return response()->json([
            'message' => 'Пополнение будет настроено после интеграции платёжного провайдера.',
            'errors' => (object) [],
        ], 501);
    }

    public function depositCallback(DepositCallbackRequest $request): JsonResponse
    {
        $request->validated();

        return response()->json([
            'data' => ['accepted' => true],
        ]);
    }

    public function withdraw(WithdrawBalanceRequest $request): JsonResponse
    {
        $request->validated();

        return response()->json([
            'message' => 'Заявка на вывод будет обрабатываться через админку.',
            'errors' => (object) [],
        ], 501);
    }
}
