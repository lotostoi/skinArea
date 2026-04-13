<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserEmailVerificationNotificationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->email === null || trim($user->email) === '') {
            return response()->json([
                'message' => 'Сначала укажите email в профиле.',
                'errors' => (object) [],
            ], 422);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email уже подтверждён.',
                'errors' => (object) [],
            ], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Письмо со ссылкой отправлено. Проверьте почту (или Mailpit).',
            'errors' => (object) [],
        ], 202);
    }
}
