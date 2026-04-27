<?php

declare(strict_types=1);

use App\Support\DemoDataMarkers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table): void {
            $table->boolean('is_manual_admin_case')->default(true)->after('is_featured_on_home');
        });

        $importedCategoryIds = DB::table('case_categories')
            ->whereIn('name', [
                DemoDataMarkers::CASE_CATEGORY_NAME,
                DemoDataMarkers::IMPORTED_CASE_CATEGORY_NAME,
            ])
            ->pluck('id');

        if ($importedCategoryIds->isNotEmpty()) {
            DB::table('cases')
                ->whereIn('category_id', $importedCategoryIds->all())
                ->update(['is_manual_admin_case' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table): void {
            $table->dropColumn('is_manual_admin_case');
        });
    }
};
