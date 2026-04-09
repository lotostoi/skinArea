<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ListSteamAssetForSale;
use App\Enums\MarketItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMarketItemRequest;
use App\Http\Requests\MarketItemIndexRequest;
use App\Http\Resources\MarketItemResource;
use App\Models\MarketItem;
use Illuminate\Http\JsonResponse;

class MarketItemController extends Controller
{
    public function index(MarketItemIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 20);

        $query = MarketItem::query()
            ->active()
            ->with('seller');

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }
        if (! empty($validated['wear'])) {
            $query->where('wear', $validated['wear']);
        }
        if (isset($validated['price_min'])) {
            $query->where('price', '>=', $validated['price_min']);
        }
        if (isset($validated['price_max'])) {
            $query->where('price', '<=', $validated['price_max']);
        }
        if (! empty($validated['search'])) {
            $q = '%'.addcslashes($validated['search'], '%_\\').'%';
            $query->where('name', 'ilike', $q);
        }

        $items = $query
            ->orderBy('price')
            ->paginate($perPage)
            ->withQueryString();

        return MarketItemResource::collection($items)->response();
    }

    public function show(MarketItem $marketItem): JsonResponse
    {
        $marketItem->load('seller');

        return MarketItemResource::make($marketItem)->response();
    }

    public function store(CreateMarketItemRequest $request, ListSteamAssetForSale $action): JsonResponse
    {
        $item = $action->execute($request->user(), $request->validated());

        return MarketItemResource::make($item->load('seller'))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(MarketItem $marketItem): JsonResponse
    {
        $this->authorize('delete', $marketItem);

        if ($marketItem->status !== MarketItemStatus::Active) {
            return response()->json([
                'message' => 'Снять с продажи можно только активный лот.',
                'errors' => (object) [],
            ], 422);
        }

        $marketItem->update([
            'status' => MarketItemStatus::Cancelled,
        ]);

        return MarketItemResource::make($marketItem->fresh()->load('seller'))->response();
    }
}
