<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\SkinPriceProviderInterface;
use App\Models\CaseOpening;
use App\Models\Deal;
use App\Models\Upgrade;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\CasePrizeFundService;
use App\Services\DemoVisibilityService;
use App\Services\Prices\PricempirePriceProvider;
use App\Services\Prices\SteamPriceProvider;
use App\Services\SteamInventoryService;
use App\Services\SteamPlayerBansService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Steam\Provider as SteamProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CasePrizeFundService::class);
        $this->app->singleton(DemoVisibilityService::class);
        $this->app->singleton(SteamInventoryService::class);
        $this->app->singleton(SteamPlayerBansService::class);

        $this->app->bind(SkinPriceProviderInterface::class, static function (): SkinPriceProviderInterface {
            $provider = (string) config('skinsarena.skin_prices.provider', 'steam');

            return match ($provider) {
                'pricempire' => new PricempirePriceProvider,
                default => new SteamPriceProvider,
            };
        });
    }

    public function boot(): void
    {
        RateLimiter::for('balance-callback', static function (Request $request): Limit {
            $maxAttempts = max(1, (int) config('skinsarena.balance.deposit_callback.throttle_per_minute', 60));
            $signatureHeader = (string) config('skinsarena.balance.deposit_callback.signature_header', 'X-SkinsArena-Signature');
            $signature = (string) $request->headers->get($signatureHeader, '');

            return Limit::perMinute($maxAttempts)
                ->by($request->ip().'|'.$signature);
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Подтвердите email — '.config('app.name'))
                ->greeting('Здравствуйте')
                ->line('Нажмите кнопку ниже, чтобы подтвердить адрес электронной почты для аккаунта SkinsArena.')
                ->action('Подтвердить email', $url)
                ->line('Если вы не запрашивали это письмо, проигнорируйте его.');
        });

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
