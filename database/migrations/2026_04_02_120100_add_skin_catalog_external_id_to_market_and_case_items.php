<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_items', function (Blueprint $table): void {
            $table->string('skin_catalog_external_id')->nullable()->after('status');
            $table->index('skin_catalog_external_id');
        });

        Schema::table('case_items', function (Blueprint $table): void {
            $table->string('skin_catalog_external_id')->nullable()->after('rarity');
            $table->index('skin_catalog_external_id');
        });
    }

    public function down(): void
    {
        Schema::table('market_items', function (Blueprint $table): void {
            $table->dropIndex(['skin_catalog_external_id']);
            $table->dropColumn('skin_catalog_external_id');
        });

        Schema::table('case_items', function (Blueprint $table): void {
            $table->dropIndex(['skin_catalog_external_id']);
            $table->dropColumn('skin_catalog_external_id');
        });
    }
};
