<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skin_catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('name');
            $table->string('image_url', 2048)->nullable();
            $table->string('rarity', 64)->nullable();
            $table->string('category', 64)->nullable();
            $table->string('weapon_name')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skin_catalog_items');
    }
};
