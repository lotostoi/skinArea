<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportMessageRequest;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Http\Resources\SupportMessageResource;
use App\Http\Resources\SupportTicketApiResource;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->paginate(20);

        return SupportTicketApiResource::collection($tickets)->response();
    }

    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $ticket = SupportTicket::query()->create([
            'user_id' => $request->user()->id,
            'subject' => $request->validated('subject'),
            'status' => SupportTicketStatus::Open,
        ]);

        return response()->json([
            'data' => new SupportTicketApiResource($ticket),
        ], 201);
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorize('view', $supportTicket);

        $supportTicket->load('messages');

        return response()->json([
            'data' => new SupportTicketApiResource($supportTicket),
        ]);
    }

    public function storeMessage(StoreSupportMessageRequest $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorize('addMessage', $supportTicket);

        $user = $request->user();

        if ($user->isSupportMuted()) {
            return response()->json([
                'message' => 'Отправка сообщений в техподдержку временно ограничена.',
                'errors' => (object) [],
            ], 422);
        }

        $message = $supportTicket->messages()->create([
            'user_id' => $user->id,
            'is_staff' => false,
            'body' => $request->validated('body'),
        ]);

        $supportTicket->touch();

        return response()->json([
            'data' => new SupportMessageResource($message),
        ], 201);
    }
}
