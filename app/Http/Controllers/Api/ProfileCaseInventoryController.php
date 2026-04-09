<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellCaseOpeningRequest;
use App\Http\Requests\WithdrawCaseOpeningRequest;
use App\Http\Resources\CaseOpeningResource;
use App\Models\CaseOpening;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileCaseInventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $openings = CaseOpening::query()
            ->where('user_id', $user->id)
            ->with(['gameCase', 'caseItem'])
            ->latest('created_at')
            ->paginate(perPage: (int) $request->query('per_page', 20))
            ->withQueryString();

        return CaseOpeningResource::collection($openings)->response();
    }

    public function sell(SellCaseOpeningRequest $request, CaseOpening $caseOpening): JsonResponse
    {
        $this->authorize('manage', $caseOpening);
        $request->validated();

        return response()->json([
            'message' => 'Продажа приза за баланс будет реализована в Action.',
            'errors' => (object) [],
        ], 501);
    }

    public function withdraw(WithdrawCaseOpeningRequest $request, CaseOpening $caseOpening): JsonResponse
    {
        $this->authorize('manage', $caseOpening);
        $request->validated();

        return response()->json([
            'message' => 'Вывод в Steam будет реализован после интеграции трейдов.',
            'errors' => (object) [],
        ], 501);
    }
}
