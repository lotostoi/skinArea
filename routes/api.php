<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\CartPurchaseController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\CurrentUserController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\MarketItemController;
use App\Http\Controllers\Api\ProfileCaseInventoryController;
use App\Http\Controllers\Api\ProfileListingController;
use App\Http\Controllers\Api\SteamInventoryController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UpgradeController;
use App\Http\Controllers\Api\UserEmailController;
use App\Http\Controllers\Api\UserEmailVerificationNotificationController;
use App\Http\Controllers\Api\UserTradeUrlController;
use App\Http\Controllers\SteamAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/steam/exchange', [SteamAuthController::class, 'exchange']);

    Route::get('market/items', [MarketItemController::class, 'index']);
    Route::get('market/items/{market_item}', [MarketItemController::class, 'show']);

    Route::get('cases/featured', [CaseController::class, 'featured']);
    Route::get('cases', [CaseController::class, 'index']);
    Route::get('cases/{caseId}', [CaseController::class, 'show'])->whereNumber('caseId');

    Route::post('balance/deposit/callback', [BalanceController::class, 'depositCallback']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [SteamAuthController::class, 'logout']);

        Route::get('user', [CurrentUserController::class, 'show']);
        Route::patch('user/trade-url', [UserTradeUrlController::class, 'update']);
        Route::patch('user/email', [UserEmailController::class, 'update']);
        Route::post('user/email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1');

        Route::get('upgrade/items', [UpgradeController::class, 'items']);

        Route::post('market/items', [MarketItemController::class, 'store']);
        Route::delete('market/items/{market_item}', [MarketItemController::class, 'destroy']);

        Route::post('cart/purchase', [CartPurchaseController::class, 'store']);

        Route::get('deals', [DealController::class, 'index']);
        Route::post('deals/{deal}/send', [DealController::class, 'send']);
        Route::post('deals/{deal}/cancel', [DealController::class, 'cancel']);

        Route::get('balance', [BalanceController::class, 'index']);
        Route::post('balance/deposit', [BalanceController::class, 'deposit']);
        Route::post('balance/withdraw', [BalanceController::class, 'withdraw']);

        Route::get('inventory/steam', [SteamInventoryController::class, 'index']);

        Route::get('profile/listings', [ProfileListingController::class, 'listings']);
        Route::get('profile/sold', [ProfileListingController::class, 'sold']);

        Route::get('transactions', [TransactionController::class, 'index']);

        Route::post('cases/{caseId}/open', [CaseController::class, 'open'])->whereNumber('caseId');

        Route::get('profile/case-inventory', [ProfileCaseInventoryController::class, 'index']);
        Route::post('profile/case-inventory/{case_opening}/sell', [ProfileCaseInventoryController::class, 'sell']);
        Route::post('profile/case-inventory/{case_opening}/withdraw', [ProfileCaseInventoryController::class, 'withdraw']);

        Route::post('upgrade', [UpgradeController::class, 'store']);

        Route::get('support/tickets', [SupportTicketController::class, 'index']);
        Route::post('support/tickets', [SupportTicketController::class, 'store']);
        Route::get('support/tickets/{support_ticket}', [SupportTicketController::class, 'show']);
        Route::post('support/tickets/{support_ticket}/messages', [SupportTicketController::class, 'storeMessage']);
    });
});
