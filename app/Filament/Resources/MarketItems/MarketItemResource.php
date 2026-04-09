<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketItems;

use App\Filament\Resources\MarketItems\Pages\EditMarketItem;
use App\Filament\Resources\MarketItems\Pages\ListMarketItems;
use App\Filament\Resources\MarketItems\Schemas\MarketItemForm;
use App\Filament\Resources\MarketItems\Tables\MarketItemsTable;
use App\Models\MarketItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MarketItemResource extends Resource
{
    protected static ?string $model = MarketItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Маркетплейс';

    protected static ?string $modelLabel = 'лот';

    protected static ?string $pluralModelLabel = 'Лоты (модерация)';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MarketItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketItems::route('/'),
            'edit' => EditMarketItem::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('seller');
    }
}
