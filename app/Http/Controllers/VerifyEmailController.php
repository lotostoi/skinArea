<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            throw new HttpException(403, 'Ссылка недействительна или истекла.');
        }

        $user = User::query()->findOrFail((int) $id);

        if ($user->email === null || $user->email === '') {
            throw new HttpException(403, 'У аккаунта не указан email.');
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new HttpException(403, 'Неверная подпись ссылки.');
        }

        if (!$user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        $frontend = rtrim((string) config('skinsarena.frontend_url'), '/');

        return redirect()->away($frontend.'/profile?email_verified=1');
    }
}
