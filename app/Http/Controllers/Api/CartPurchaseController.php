<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Market\PurchaseCart;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseCartRequest;
use App\Http\Resources\DealResource;
use Illuminate\Database\Eloquent\Collection;
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

        $collection = new Collection($deals);
        $collection->loadMissing('marketItem');

        return DealResource::collection($collection)
            ->response()
            ->setStatusCode(201);
    }
}
