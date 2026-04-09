<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUpgradeRequest;
use App\Http\Resources\UpgradeItemResource;
use Illuminate\Http\JsonResponse;

class UpgradeController extends Controller
{
    public function items(): JsonResponse
    {
        $empty = collect([]);

        return UpgradeItemResource::collection($empty)->response();
    }

    public function store(CreateUpgradeRequest $request): JsonResponse
    {
        $request->validated();

        return response()->json([
            'message' => 'Апгрейд обрабатывается через Action CreateUpgrade.',
            'errors' => (object) [],
        ], 501);
    }
}
