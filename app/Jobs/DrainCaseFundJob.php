<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GameCase;
use App\Services\CasePrizeFundService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DrainCaseFundJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly GameCase $case,
    ) {}

    public function handle(CasePrizeFundService $fundService): void
    {
        if (! $this->case->is_active) {
            return;
        }

        $fundService->drainFund($this->case);
    }

    public function failed(Throwable $exception): void
    {
        logger()->error('DrainCaseFundJob failed', [
            'case_id' => $this->case->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
