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
use App\Services\DemoVisibilityService;
use Illuminate\Http\JsonResponse;

class MarketItemController extends Controller
{
    public function __construct(
        private readonly DemoVisibilityService $demoVisibility,
    ) {}

    public function index(MarketItemIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 20);

        $query = MarketItem::query()
            ->active()
            ->with('seller');

        $this->demoVisibility->applyHideDemoToMarketItemsQuery($query);

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
        $search = isset($validated['search']) ? trim((string) $validated['search']) : '';
        if ($search !== '' && mb_strlen($search) >= 2) {
            $q = '%'.addcslashes($search, '%_\\').'%';
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

        if ($this->demoVisibility->shouldHideDemo() && $this->demoVisibility->isDemoMarketItem($marketItem)) {
            abort(404);
        }

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
