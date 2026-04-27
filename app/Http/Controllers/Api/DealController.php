<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelDealRequest;
use App\Http\Requests\PaginatedIndexRequest;
use App\Http\Requests\SendDealRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use App\Services\DemoVisibilityService;
use Illuminate\Http\JsonResponse;

class DealController extends Controller
{
    public function __construct(
        private readonly DemoVisibilityService $demoVisibility,
    ) {}

    public function index(PaginatedIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $query = Deal::query()
            ->forParticipant($user)
            ->with(['marketItem.seller'])
            ->latest();

        $this->demoVisibility->applyHideDemoToDealsQuery($query);

        $deals = $query
            ->paginate(perPage: (int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return DealResource::collection($deals)->response();
    }

    public function send(SendDealRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('send', $deal);
        $request->validated();

        return response()->json([
            'message' => 'Отправка трейда будет реализована в сервисе Steam.',
            'errors' => (object) [],
        ], 501);
    }

    public function cancel(CancelDealRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('cancel', $deal);
        $request->validated();

        return response()->json([
            'message' => 'Отмена сделки будет реализована в Action CancelDeal.',
            'errors' => (object) [],
        ], 501);
    }
}
