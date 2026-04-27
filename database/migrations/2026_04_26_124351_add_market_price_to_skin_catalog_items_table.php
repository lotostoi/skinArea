<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skin_catalog_items', function (Blueprint $table): void {
            $table->decimal('market_price', 12, 2)->nullable()->after('weapon_name')
                ->comment('Рыночная цена в USD, синхронизируется командой skins:prices-sync');
            $table->timestamp('price_synced_at')->nullable()->after('market_price')
                ->comment('Время последнего успешного обновления цены');
        });
    }

    public function down(): void
    {
        Schema::table('skin_catalog_items', function (Blueprint $table): void {
            $table->dropColumn(['market_price', 'price_synced_at']);
        });
    }
};
