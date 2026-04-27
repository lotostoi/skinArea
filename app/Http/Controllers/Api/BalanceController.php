<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Balance\ProcessFakeDeposit;
use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DepositBalanceRequest;
use App\Http\Requests\DepositCallbackRequest;
use App\Http\Requests\WithdrawBalanceRequest;
use App\Http\Resources\BalanceResource;
use App\Models\Balance;
use App\Models\Transaction;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        foreach ([BalanceType::Main, BalanceType::Bonus, BalanceType::Hold] as $type) {
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

    public function deposit(DepositBalanceRequest $request, ProcessFakeDeposit $action): JsonResponse
    {
        $user = $request->user();
        $amount = (string) $request->validated('amount');

        $transaction = $action->execute($user, $amount);

        foreach ([BalanceType::Main, BalanceType::Bonus, BalanceType::Hold] as $type) {
            Balance::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $type,
                ],
                ['amount' => 0],
            );
        }

        $user->load('balances');

        return response()->json([
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'balances' => BalanceResource::collection($user->balances),
            ],
        ]);
    }

    public function depositCallback(DepositCallbackRequest $request, LedgerService $ledger): JsonResponse
    {
        if (! $this->isDepositCallbackAuthorized($request)) {
            return response()->json([
                'message' => 'Некорректная подпись callback.',
                'errors' => (object) [],
            ], 401);
        }

        $idempotencyKey = (string) $request->validated('idempotency_key');
        $paymentStatus = (string) $request->validated('status');
        $reason = $request->validated('reason');

        $transaction = Transaction::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($transaction === null) {
            return response()->json([
                'message' => 'Транзакция не найдена.',
                'errors' => (object) [],
            ], 404);
        }

        if ($transaction->status !== TransactionStatus::Pending) {
            return response()->json([
                'data' => [
                    'accepted' => true,
                    'already_processed' => true,
                    'transaction_id' => $transaction->id,
                    'status' => $transaction->status->value,
                ],
            ]);
        }

        if ($paymentStatus === 'succeeded') {
            $ledger->post($transaction);
        } else {
            $ledger->fail($transaction, is_string($reason) ? $reason : 'Ошибка платёжной системы');
        }

        return response()->json([
            'data' => [
                'accepted' => true,
                'transaction_id' => $transaction->id,
                'status' => $transaction->fresh()->status->value,
            ],
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

    private function isDepositCallbackAuthorized(Request $request): bool
    {
        $isSignatureRequired = (bool) config('skinsarena.balance.deposit_callback.require_signature', false);

        if (! $isSignatureRequired) {
            return true;
        }

        $secret = (string) config('skinsarena.balance.deposit_callback.secret', '');
        if ($secret === '') {
            return false;
        }

        $headerName = (string) config('skinsarena.balance.deposit_callback.signature_header', 'X-SkinsArena-Signature');
        $providedSignature = (string) $request->headers->get($headerName, '');
        if ($providedSignature === '') {
            return false;
        }

        $calculatedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($calculatedSignature, $providedSignature);
    }
}
