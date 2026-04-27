<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MarketItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginatedIndexRequest;
use App\Http\Resources\MarketItemResource;
use App\Models\MarketItem;
use Illuminate\Http\JsonResponse;

class ProfileListingController extends Controller
{
    public function listings(PaginatedIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $items = MarketItem::query()
            ->where('seller_id', $user->id)
            ->where('status', MarketItemStatus::Active)
            ->latest()
            ->paginate(perPage: (int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return MarketItemResource::collection($items)->response();
    }

    public function sold(PaginatedIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $items = MarketItem::query()
            ->where('seller_id', $user->id)
            ->where('status', MarketItemStatus::Sold)
            ->latest()
            ->paginate(perPage: (int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return MarketItemResource::collection($items)->response();
    }
}
