<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases;

use App\Filament\Resources\GameCases\Pages\CreateGameCase;
use App\Filament\Resources\GameCases\Pages\EditGameCase;
use App\Filament\Resources\GameCases\Pages\ListGameCases;
use App\Filament\Resources\GameCases\RelationManagers\FundAdjustmentsRelationManager;
use App\Filament\Resources\GameCases\RelationManagers\LevelsRelationManager;
use App\Filament\Resources\GameCases\RelationManagers\OpeningsRelationManager;
use App\Filament\Resources\GameCases\Schemas\GameCaseForm;
use App\Filament\Resources\GameCases\Tables\GameCasesTable;
use App\Models\GameCase;
use App\Services\DemoVisibilityService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GameCaseResource extends Resource
{
    protected static ?string $model = GameCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Кейсы';

    protected static ?string $modelLabel = 'кейс';

    protected static ?string $pluralModelLabel = 'Кейсы';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return GameCaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameCasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LevelsRelationManager::class,
            OpeningsRelationManager::class,
            FundAdjustmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameCases::route('/'),
            'create' => CreateGameCase::route('/create'),
            'edit' => EditGameCase::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        app(DemoVisibilityService::class)->applyHideDemoToGameCasesQuery($query);

        return $query;
    }
}
