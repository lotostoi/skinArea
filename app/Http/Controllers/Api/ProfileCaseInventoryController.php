<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Cases\SellCaseOpeningAction;
use App\Actions\Cases\WithdrawCaseOpeningAction;
use App\Enums\CaseOpeningStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CaseInventoryIndexRequest;
use App\Http\Requests\SellCaseOpeningRequest;
use App\Http\Requests\WithdrawCaseOpeningRequest;
use App\Http\Resources\CaseOpeningResource;
use App\Models\CaseOpening;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ProfileCaseInventoryController extends Controller
{
    public function index(CaseInventoryIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $query = CaseOpening::query()
            ->where('user_id', $user->id)
            ->with(['gameCase', 'caseItem'])
            ->latest('created_at');

        if (isset($validated['status'])) {
            $status = CaseOpeningStatus::tryFrom($validated['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        if (isset($validated['case_id'])) {
            $query->where('case_id', (int) $validated['case_id']);
        }

        if (isset($validated['search']) && is_string($validated['search']) && trim($validated['search']) !== '') {
            $search = mb_strtolower(trim($validated['search']));
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->whereHas('caseItem', static fn (Builder $item): Builder => $item->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
                    ->orWhereHas('gameCase', static fn (Builder $case): Builder => $case->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]));
            });
        }

        $sortColumn = $validated['sort'] ?? 'created_at';
        $sortOrder = $validated['order'] ?? 'desc';
        if ($sortColumn === 'won_item_price') {
            $query->orderBy('won_item_price', $sortOrder);
        } else {
            $query->orderBy('created_at', $sortOrder);
        }

        $openings = $query
            ->paginate(perPage: (int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return CaseOpeningResource::collection($openings)->response();
    }

    public function summary(CaseInventoryIndexRequest $request): JsonResponse
    {
        $user = $request->user();

        $base = CaseOpening::query()->where('user_id', $user->id);
        $inInventory = (clone $base)->where('status', CaseOpeningStatus::InInventory);

        return response()->json([
            'data' => [
                'total_items' => (clone $base)->count(),
                'total_value' => (string) ((clone $base)->sum('won_item_price')),
                'in_inventory_items' => (clone $inInventory)->count(),
                'in_inventory_value' => (string) ((clone $inInventory)->sum('won_item_price')),
                'sold_items' => (clone $base)->where('status', CaseOpeningStatus::Sold)->count(),
                'withdrawn_items' => (clone $base)->where('status', CaseOpeningStatus::Withdrawn)->count(),
                'used_in_upgrade_items' => (clone $base)->where('status', CaseOpeningStatus::UsedInUpgrade)->count(),
            ],
        ]);
    }

    public function sell(
        SellCaseOpeningRequest $request,
        CaseOpening $caseOpening,
        SellCaseOpeningAction $action,
    ): JsonResponse {
        $this->authorize('manage', $caseOpening);
        $request->validated();

        $updated = $action->execute($request->user(), $caseOpening);

        return CaseOpeningResource::make($updated)->response();
    }

    public function withdraw(
        WithdrawCaseOpeningRequest $request,
        CaseOpening $caseOpening,
        WithdrawCaseOpeningAction $action,
    ): JsonResponse {
        $this->authorize('manage', $caseOpening);
        $validated = $request->validated();

        $updated = $action->execute(
            user: $request->user(),
            opening: $caseOpening,
            tradeUrl: $validated['trade_url'] ?? null,
        );

        return CaseOpeningResource::make($updated)->response();
    }
}
