<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\CaseOpening;
use App\Models\Deal;
use App\Models\Upgrade;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\SteamInventoryService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Steam\Provider as SteamProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SteamInventoryService::class);
    }

    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('steam', SteamProvider::class);
        });

        Relation::enforceMorphMap([
            'user' => User::class,
            'deal' => Deal::class,
            'case_opening' => CaseOpening::class,
            'upgrade' => Upgrade::class,
            'withdrawal_request' => WithdrawalRequest::class,
        ]);
    }
}
