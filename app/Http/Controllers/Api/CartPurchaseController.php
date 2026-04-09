<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseCartRequest;
use Illuminate\Http\JsonResponse;

class CartPurchaseController extends Controller
{
    public function store(PurchaseCartRequest $request): JsonResponse
    {
        $request->validated();

        return response()->json([
            'message' => 'Покупка из корзины будет реализована в Action PurchaseCart.',
            'errors' => (object) [],
        ], 501);
    }
}
