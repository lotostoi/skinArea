<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseCategories;

use App\Filament\Resources\CaseCategories\Pages\CreateCaseCategory;
use App\Filament\Resources\CaseCategories\Pages\EditCaseCategory;
use App\Filament\Resources\CaseCategories\Pages\ListCaseCategories;
use App\Filament\Resources\CaseCategories\Schemas\CaseCategoryForm;
use App\Filament\Resources\CaseCategories\Tables\CaseCategoriesTable;
use App\Models\CaseCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CaseCategoryResource extends Resource
{
    protected static ?string $model = CaseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Кейсы';

    protected static ?string $modelLabel = 'категория кейсов';

    protected static ?string $pluralModelLabel = 'Категории кейсов';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CaseCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaseCategoriesTable::configure($table);
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
            'index' => ListCaseCategories::route('/'),
            'create' => CreateCaseCategory::route('/create'),
            'edit' => EditCaseCategory::route('/{record}/edit'),
        ];
    }
}
