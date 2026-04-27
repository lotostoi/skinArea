<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameCase;
use App\Models\MarketItem;
use App\Models\SiteSetting;
use App\Support\DemoDataMarkers;
use Illuminate\Database\Eloquent\Builder;

final class DemoVisibilityService
{
    public function isDemoPublicEnabled(): bool
    {
        return SiteSetting::showDemoData();
    }

    public function shouldHideDemo(): bool
    {
        return ! $this->isDemoPublicEnabled();
    }

    public function applyHideDemoToMarketItemsQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $query->where(function (Builder $outer): void {
            $outer->whereDoesntHave('seller', function (Builder $seller): void {
                DemoDataMarkers::whereQueryIsDemoSeller($seller);
            })->where(function (Builder $asset): void {
                $asset->where('asset_id', 'not like', DemoDataMarkers::LISTING_ASSET_PREFIX.'%')
                    ->where('asset_id', 'not like', DemoDataMarkers::SOLD_ASSET_PREFIX.'%');
            });
        });
    }

    public function applyHideDemoToGameCasesQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $this->applyPublicCatalogCaseVisibilityToGameCasesQuery($query);
    }

    public function applyHideDemoToUsersQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $query->where(function (Builder $q): void {
            $q->where('steam_id', 'not like', DemoDataMarkers::STEAM_ID_PREFIX.'%')
                ->orWhere('username', 'not like', DemoDataMarkers::USERNAME_PREFIX.'%');
        });
    }

    public function applyHideDemoToCaseCategoriesQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $query->where('name', '!=', DemoDataMarkers::CASE_CATEGORY_NAME);
    }

    public function applyHideDemoToCaseLevelsQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $query->whereHas('gameCase', function (Builder $gameCase): void {
            $this->applyPublicCatalogCaseVisibilityToGameCasesQuery($gameCase);
        });
    }

    public function applyHideDemoToCaseItemsQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $query->whereHas('level.gameCase', function (Builder $gameCase): void {
            $this->applyPublicCatalogCaseVisibilityToGameCasesQuery($gameCase);
        });
    }

    public function applyHideDemoToBalancesQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $query->whereHas('user', function (Builder $user): void {
            $user->where(function (Builder $q): void {
                $q->where('steam_id', 'not like', DemoDataMarkers::STEAM_ID_PREFIX.'%')
                    ->orWhere('username', 'not like', DemoDataMarkers::USERNAME_PREFIX.'%');
            });
        });
    }

    public function applyHideDemoToTransactionsQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $query->whereHas('user', function (Builder $user): void {
            $user->where(function (Builder $q): void {
                $q->where('steam_id', 'not like', DemoDataMarkers::STEAM_ID_PREFIX.'%')
                    ->orWhere('username', 'not like', DemoDataMarkers::USERNAME_PREFIX.'%');
            });
        });
    }

    public function applyHideDemoToCaseOpeningsFeedQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $query->whereDoesntHave('user', function (Builder $user): void {
            DemoDataMarkers::whereQueryIsDemoSeller($user);
        })->whereHas('gameCase', function (Builder $gameCase): void {
            $this->applyPublicCatalogCaseVisibilityToGameCasesQuery($gameCase);
        });
    }

    public function applyHideDemoToDealsQuery(Builder $query): void
    {
        if (! $this->shouldHideDemo()) {
            return;
        }

        $query->whereDoesntHave('marketItem', function (Builder $mi): void {
            $mi->where(function (Builder $inner): void {
                $inner->whereHas('seller', function (Builder $seller): void {
                    DemoDataMarkers::whereQueryIsDemoSeller($seller);
                })
                    ->orWhere('asset_id', 'like', DemoDataMarkers::LISTING_ASSET_PREFIX.'%')
                    ->orWhere('asset_id', 'like', DemoDataMarkers::SOLD_ASSET_PREFIX.'%');
            });
        });
    }

    public function isDemoMarketItem(MarketItem $item): bool
    {
        $item->loadMissing('seller');

        return DemoDataMarkers::isDemoSellerUser($item->seller)
            || DemoDataMarkers::isDemoAssetId($item->asset_id);
    }

    /**
     * При выключенном показе демо: скрыть импортированные/демо-кейсы; оставить только созданные вручную в админке
     * (и не в категории «Демо-кейсы»).
     */
    public function isDemoGameCase(GameCase $case): bool
    {
        $case->loadMissing('category');

        if ($case->category !== null && $case->category->name === DemoDataMarkers::CASE_CATEGORY_NAME) {
            return true;
        }

        return ! $case->is_manual_admin_case;
    }

    private function applyPublicCatalogCaseVisibilityToGameCasesQuery(Builder $query): void
    {
        $query->where('is_manual_admin_case', true)
            ->whereDoesntHave('category', function (Builder $category): void {
                $category->where('name', DemoDataMarkers::CASE_CATEGORY_NAME);
            });
    }
}
