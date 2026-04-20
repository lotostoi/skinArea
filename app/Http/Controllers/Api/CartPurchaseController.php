<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Market\PurchaseCart;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseCartRequest;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CartPurchaseController extends Controller
{
    public function store(PurchaseCartRequest $request, PurchaseCart $action): JsonResponse
    {
        $ids = array_map('intval', (array) $request->validated('market_item_ids'));

        try {
            $deals = $action->execute($request->user(), $ids);
        } catch (InsufficientBalanceException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => (object) [],
            ], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => (object) [],
            ], 422);
        }

        $data = array_map(fn ($d) => [
            'id' => $d->id,
            'market_item_id' => $d->market_item_id,
            'price' => (string) $d->price,
            'commission' => (string) $d->commission,
            'status' => $d->status->value,
            'expires_at' => $d->expires_at,
            'created_at' => $d->created_at,
        ], $deals);

        return response()->json([
            'data' => $data,
        ], 201);
    }
}
