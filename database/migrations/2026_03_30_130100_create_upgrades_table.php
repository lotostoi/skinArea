<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upgrades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('bet_amount', 12, 2);
            $table->string('target_item_name');
            $table->decimal('target_item_price', 12, 2);
            $table->decimal('chance', 5, 2);
            $table->boolean('is_won')->default(false);
            $table->foreignId('won_case_opening_id')->nullable()->constrained('case_openings')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upgrades');
    }
};
