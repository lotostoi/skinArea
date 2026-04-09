<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SteamExchangeCodeRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\SteamLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SteamAuthController extends Controller
{
    private const AUTH_CODE_TTL_SECONDS = 300;

    public function __construct(
        private readonly SteamLoginService $steamLoginService,
    ) {}

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('steam')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim((string) config('skinsarena.frontend_url'), '/');

        try {
            $socialiteUser = Socialite::driver('steam')->user();
            $user = $this->steamLoginService->findOrUpdateUser($socialiteUser);
        } catch (Throwable) {
            return redirect()->away($frontendUrl.'/auth/steam-error?reason=steam_denied');
        }

        if ($user->is_banned) {
            $reason = $user->ban_reason !== null
                ? '&reason='.rawurlencode((string) $user->ban_reason)
                : '';

            return redirect()->away($frontendUrl.'/auth/steam-error?reason=banned'.$reason);
        }

        $plainToken = $user->createToken('spa')->plainTextToken;
        $code = Str::random(64);

        Cache::put(
            $this->cacheKey($code),
            ['token' => $plainToken, 'user_id' => $user->id],
            self::AUTH_CODE_TTL_SECONDS,
        );

        return redirect()->away($frontendUrl.'/auth/steam-complete?code='.$code);
    }

    public function exchange(SteamExchangeCodeRequest $request): JsonResponse
    {
        $code = $request->validated('code');
        $payload = Cache::pull($this->cacheKey((string) $code));

        if (! is_array($payload) || ! isset($payload['token'], $payload['user_id'])) {
            return response()->json([
                'message' => 'Неверный или просроченный код. Войдите через Steam снова.',
                'errors' => (object) [],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::query()->find($payload['user_id']);

        if ($user === null || $user->is_banned) {
            return response()->json([
                'message' => 'Доступ запрещён.',
                'errors' => (object) [],
            ], Response::HTTP_FORBIDDEN);
        }

        $user->load('balances');

        return response()->json([
            'data' => [
                'token' => $payload['token'],
                'token_type' => 'Bearer',
                'user' => (new UserResource($user))->toArray($request),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['data' => ['logged_out' => true]]);
    }

    private function cacheKey(string $code): string
    {
        return 'steam_auth_code:'.hash('sha256', $code);
    }
}
