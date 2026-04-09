<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('name');
            $table->decimal('chance', 5, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['case_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_levels');
    }
};
