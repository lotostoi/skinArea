<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseItems;

use App\Filament\Resources\CaseItems\Pages\CreateCaseItem;
use App\Filament\Resources\CaseItems\Pages\EditCaseItem;
use App\Filament\Resources\CaseItems\Pages\ListCaseItems;
use App\Filament\Resources\CaseItems\Schemas\CaseItemForm;
use App\Filament\Resources\CaseItems\Tables\CaseItemsTable;
use App\Models\CaseItem;
use App\Services\DemoVisibilityService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CaseItemResource extends Resource
{
    protected static ?string $model = CaseItem::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CaseItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaseItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaseItems::route('/'),
            'create' => CreateCaseItem::route('/create'),
            'edit' => EditCaseItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        app(DemoVisibilityService::class)->applyHideDemoToCaseItemsQuery($query);

        return $query;
    }
}
