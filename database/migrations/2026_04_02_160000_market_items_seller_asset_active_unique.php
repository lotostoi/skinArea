<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE UNIQUE INDEX market_items_seller_asset_active_unique ON market_items (seller_id, asset_id) WHERE status = \'active\'',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS market_items_seller_asset_active_unique');
    }
};
