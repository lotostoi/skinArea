<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpenCaseRequest;
use App\Http\Resources\GameCaseResource;
use App\Models\GameCase;
use Illuminate\Http\JsonResponse;

class CaseController extends Controller
{
    public function index(): JsonResponse
    {
        $cases = GameCase::query()
            ->active()
            ->orderBy('sort_order')
            ->paginate(20);

        return GameCaseResource::collection($cases)->response();
    }

    public function show(int $caseId): JsonResponse
    {
        $case = GameCase::query()
            ->active()
            ->whereKey($caseId)
            ->with(['levels.items'])
            ->firstOrFail();

        return GameCaseResource::make($case)->response();
    }

    public function open(OpenCaseRequest $request, int $caseId): JsonResponse
    {
        $request->validated();
        GameCase::query()->active()->whereKey($caseId)->firstOrFail();

        return response()->json([
            'message' => 'Открытие кейса выполняется только через Action OpenCase.',
            'errors' => (object) [],
        ], 501);
    }
}
