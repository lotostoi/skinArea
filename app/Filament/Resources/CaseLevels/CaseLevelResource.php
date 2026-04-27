<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseLevels;

use App\Filament\Resources\CaseLevels\Pages\AddSkinsToCaseLevel;
use App\Filament\Resources\CaseLevels\Pages\CreateCaseLevel;
use App\Filament\Resources\CaseLevels\Pages\EditCaseLevel;
use App\Filament\Resources\CaseLevels\Pages\ListCaseLevels;
use App\Filament\Resources\CaseLevels\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\CaseLevels\Schemas\CaseLevelForm;
use App\Filament\Resources\CaseLevels\Tables\CaseLevelsTable;
use App\Models\CaseLevel;
use App\Services\DemoVisibilityService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CaseLevelResource extends Resource
{
    protected static ?string $model = CaseLevel::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CaseLevelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaseLevelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaseLevels::route('/'),
            'create' => CreateCaseLevel::route('/create'),
            'edit' => EditCaseLevel::route('/{record}/edit'),
            'add-skins' => AddSkinsToCaseLevel::route('/{record}/add-skins'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        app(DemoVisibilityService::class)->applyHideDemoToCaseLevelsQuery($query);

        return $query;
    }
}
