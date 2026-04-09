<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelDealRequest;
use App\Http\Requests\SendDealRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $deals = Deal::query()
            ->forParticipant($user)
            ->with(['marketItem.seller'])
            ->latest()
            ->paginate(perPage: (int) $request->query('per_page', 20))
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
