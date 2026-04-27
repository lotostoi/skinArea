<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Cases\OpenCaseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OpenCaseRequest;
use App\Http\Resources\CaseOpeningFeedResource;
use App\Http\Resources\CaseOpeningResource;
use App\Http\Resources\GameCaseResource;
use App\Models\CaseOpening;
use App\Models\GameCase;
use App\Services\DemoVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class CaseController extends Controller
{
    public function __construct(
        private readonly DemoVisibilityService $demoVisibility,
    ) {}

    public function index(): JsonResponse
    {
        $query = GameCase::query()
            ->active()
            ->with('category')
            ->orderBy('sort_order');

        $this->demoVisibility->applyHideDemoToGameCasesQuery($query);

        $cases = $query->get();

        return GameCaseResource::collection($cases)->response();
    }

    public function featured(): JsonResponse
    {
        $query = GameCase::query()
            ->active()
            ->featuredOnHome()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('id');

        $this->demoVisibility->applyHideDemoToGameCasesQuery($query);

        $cases = $query->get();

        return GameCaseResource::collection($cases)->response();
    }

    public function show(int $caseId): JsonResponse
    {
        $case = GameCase::query()
            ->active()
            ->whereKey($caseId)
            ->with(['category', 'levels' => fn ($q) => $q->orderBy('level'), 'levels.items'])
            ->firstOrFail();

        if ($this->demoVisibility->shouldHideDemo() && $this->demoVisibility->isDemoGameCase($case)) {
            abort(404);
        }

        return GameCaseResource::make($case)->response();
    }

    public function live(): JsonResponse
    {
        $query = CaseOpening::query()
            ->with([
                'user:id,username,avatar_url',
                'gameCase:id,name',
                'caseItem:id,name,image_url,rarity',
            ])
            ->latest('created_at')
            ->limit(40);

        $this->demoVisibility->applyHideDemoToCaseOpeningsFeedQuery($query);

        $openings = $query->get();

        return CaseOpeningFeedResource::collection($openings)->response();
    }

    public function open(OpenCaseRequest $request, int $caseId, OpenCaseAction $action): JsonResponse
    {
        $validated = $request->validated();
        $case = GameCase::query()->active()->whereKey($caseId)->firstOrFail();

        if ($this->demoVisibility->shouldHideDemo() && $this->demoVisibility->isDemoGameCase($case)) {
            abort(404);
        }
        $quantity = (int) ($validated['quantity'] ?? 1);
        $fastMode = (bool) ($validated['fast'] ?? false);

        if ($quantity === 1) {
            $opening = $action->execute($request->user(), $case);

            return CaseOpeningResource::make($opening)->response();
        }

        $openings = Collection::times($quantity, function () use ($request, $case, $action) {
            return $action->execute($request->user(), $case);
        });

        return response()->json([
            'data' => CaseOpeningResource::collection($openings)->resolve($request),
            'meta' => [
                'quantity' => $quantity,
                'fast' => $fastMode,
            ],
        ], 201);
    }
}
