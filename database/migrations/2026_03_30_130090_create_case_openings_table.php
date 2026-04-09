<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('case_item_id')->constrained('case_items')->restrictOnDelete();
            $table->decimal('cost', 12, 2);
            $table->decimal('won_item_price', 12, 2);
            $table->string('status', 32)->default('in_inventory');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_openings');
    }
};
