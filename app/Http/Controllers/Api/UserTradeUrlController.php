<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserTradeUrlRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class UserTradeUrlController extends Controller
{
    public function update(UpdateUserTradeUrlRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'trade_url' => trim($request->validated('trade_url')),
        ]);

        return UserResource::make($user->fresh(['balances']))->response();
    }
}
