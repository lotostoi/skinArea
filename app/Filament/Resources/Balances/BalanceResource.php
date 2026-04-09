<?php

declare(strict_types=1);

namespace App\Filament\Resources\Balances;

use App\Filament\Resources\Balances\Pages\ListBalances;
use App\Filament\Resources\Balances\Schemas\BalanceForm;
use App\Filament\Resources\Balances\Tables\BalancesTable;
use App\Models\Balance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BalanceResource extends Resource
{
    protected static ?string $model = Balance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Финансы';

    protected static ?string $modelLabel = 'баланс';

    protected static ?string $pluralModelLabel = 'Балансы';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return BalanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BalancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBalances::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }
}
