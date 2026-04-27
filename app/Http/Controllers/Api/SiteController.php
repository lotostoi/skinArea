<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DemoVisibilityService;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    public function __invoke(DemoVisibilityService $demoVisibility): JsonResponse
    {
        return response()->json([
            'data' => [
                'show_demo_data' => $demoVisibility->isDemoPublicEnabled(),
            ],
        ]);
    }
}
