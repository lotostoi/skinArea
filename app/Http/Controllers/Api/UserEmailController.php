<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserEmailController extends Controller
{
    public function update(UpdateUserEmailRequest $request): JsonResponse
    {
        $user = $request->user();
        $email = $request->validated('email');
        $user->email = $email;
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();
        $user->load('balances');

        $userId = $user->id;
        if ($user->email !== null && trim($user->email) !== '' && !$user->hasVerifiedEmail()) {
            dispatch(function () use ($userId): void {
                $fresh = User::query()->find($userId);
                if ($fresh === null) {
                    return;
                }
                if ($fresh->email !== null && trim((string) $fresh->email) !== '' && !$fresh->hasVerifiedEmail()) {
                    $fresh->sendEmailVerificationNotification();
                }
            })->afterResponse();
        }

        return UserResource::make($user->fresh(['balances']))->response();
    }
}
