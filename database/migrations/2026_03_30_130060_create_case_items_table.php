<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_level_id')->constrained('case_levels')->cascadeOnDelete();
            $table->string('name');
            $table->string('image_url', 1024)->nullable();
            $table->decimal('price', 12, 2);
            $table->string('wear', 8);
            $table->string('rarity', 32);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_items');
    }
};
