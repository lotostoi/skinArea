<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_levels', function (Blueprint $table): void {
            $table->decimal('prize_amount', 12, 2)->default(0)->after('chance');
        });
    }

    public function down(): void
    {
        Schema::table('case_levels', function (Blueprint $table): void {
            $table->dropColumn('prize_amount');
        });
    }
};
