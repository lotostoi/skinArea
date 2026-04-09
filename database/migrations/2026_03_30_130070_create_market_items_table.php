<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('asset_id', 64);
            $table->string('name');
            $table->string('image_url', 1024)->nullable();
            $table->string('wear', 8);
            $table->decimal('float_value', 20, 18)->nullable();
            $table->string('rarity', 32);
            $table->string('category', 32);
            $table->decimal('price', 12, 2);
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index(['status', 'price']);
            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_items');
    }
};
