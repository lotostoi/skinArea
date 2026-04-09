<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\WithdrawalRequestStatus;
use App\Models\User;
use App\Models\WithdrawalRequest;
use InvalidArgumentException;

class RejectWithdrawalRequest
{
    public function execute(WithdrawalRequest $request, User $admin, ?string $comment = null): void
    {
        if ($request->status !== WithdrawalRequestStatus::Pending) {
            throw new InvalidArgumentException('Отклонить можно только заявку в статусе «ожидает».');
        }

        $request->update([
            'status' => WithdrawalRequestStatus::Rejected,
            'admin_comment' => $comment,
            'processed_at' => now(),
            'processed_by' => $admin->id,
        ]);
    }
}
