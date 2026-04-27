<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseLevels\RelationManagers;

use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Models\CaseItem;
use App\Models\SkinCatalogItem;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Призы уровня';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('skin_catalog_external_id')
                    ->label('Скин из каталога')
                    ->placeholder('Введите название скина...')
                    ->searchable()
                    ->getSearchResultsUsing(static function (string $search): array {
                        return SkinCatalogItem::query()
                            ->where('name', 'ilike', "%{$search}%")
                            ->orderBy('name')
                            ->limit(30)
                            ->pluck('name', 'external_id')
                            ->all();
                    })
                    ->getOptionLabelUsing(static function (?string $value): ?string {
                        if ($value === null) {
                            return null;
                        }

                        return SkinCatalogItem::where('external_id', $value)->value('name');
                    })
                    ->afterStateUpdated(static function (?string $state, Set $set): void {
                        if ($state === null) {
                            return;
                        }
                        $item = SkinCatalogItem::where('external_id', $state)->first();
                        if ($item === null) {
                            return;
                        }
                        $set('name', $item->name);
                        $set('image_url', $item->image_url ?? '');
                        if ($item->rarity !== null) {
                            $set('rarity', $item->rarity);
                        }
                        if ($item->market_price !== null) {
                            $set('price', (string) $item->market_price);
                        }
                    })
                    ->live()
                    ->helperText('Начните вводить название скина CS2.'),

                Placeholder::make('catalog_preview')
                    ->label('Превью')
                    ->content(static function ($get): string {
                        $externalId = $get('skin_catalog_external_id');
                        if (! $externalId) {
                            return 'Выберите скин для предпросмотра.';
                        }
                        $item = SkinCatalogItem::where('external_id', $externalId)->first();
                        if (! $item || ! $item->image_url) {
                            return 'Изображение недоступно.';
                        }

                        return '<img src="'.e($item->image_url).'" alt="'.e($item->name).'" style="height:80px;object-fit:contain;">';
                    })
                    ->extraAttributes(['class' => 'min-h-[80px]'])
                    ->visible(static fn ($get): bool => (bool) $get('skin_catalog_external_id')),

                TextInput::make('name')
                    ->label('Название приза')
                    ->required()
                    ->maxLength(255),

                TextInput::make('image_url')
                    ->label('URL изображения')
                    ->url()
                    ->maxLength(1024),

                TextInput::make('price')
                    ->label('Цена, ₽')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('₽'),

                Select::make('wear')
                    ->label('Износ')
                    ->options(ItemWear::class)
                    ->required(),

                Select::make('rarity')
                    ->label('Редкость')
                    ->options(ItemRarity::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('rarity')
            ->columns([
                ImageColumn::make('image_url')
                    ->label('')
                    ->getStateUsing(static fn ($record): ?string => $record->image_url)
                    ->size(48)
                    ->extraImgAttributes(['style' => 'object-fit:contain']),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('price')
                    ->label('Цена, ₽')
                    ->money('RUB')
                    ->sortable(),
                TextColumn::make('wear')
                    ->label('Износ')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ItemWear ? $state->getLabel() : $state),
                TextColumn::make('rarity')
                    ->label('Редкость')
                    ->badge()
                    ->color(fn ($state): string => match ((string) ($state instanceof ItemRarity ? $state->value : $state)) {
                        'consumer_grade' => 'gray',
                        'industrial_grade' => 'info',
                        'mil_spec' => 'primary',
                        'restricted' => 'warning',
                        'classified' => 'danger',
                        'covert' => 'danger',
                        'contraband' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof ItemRarity ? $state->getLabel() : $state),
            ])
            ->filters([])
            ->headerActions([
                Action::make('bulk_add_from_catalog')
                    ->label('Добавить из каталога')
                    ->icon('heroicon-o-squares-plus')
                    ->color('primary')
                    ->form([
                        Select::make('filter_rarity')
                            ->label('Редкость')
                            ->options(ItemRarity::class)
                            ->placeholder('Любая редкость')
                            ->live(),

                        Select::make('filter_weapon')
                            ->label('Оружие / тип')
                            ->placeholder('Любой тип')
                            ->options(static function (): array {
                                return SkinCatalogItem::query()
                                    ->whereNotNull('weapon_name')
                                    ->where('weapon_name', '!=', '')
                                    ->distinct()
                                    ->orderBy('weapon_name')
                                    ->pluck('weapon_name', 'weapon_name')
                                    ->all();
                            })
                            ->searchable()
                            ->live(),

                        TextInput::make('filter_name')
                            ->label('Поиск по названию')
                            ->placeholder('AWP Dragon Lore...')
                            ->live(debounce: 400),

                        TextInput::make('filter_price_max')
                            ->label('Цена не выше, ₽')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('без ограничений')
                            ->live(debounce: 400),

                        Placeholder::make('results_count')
                            ->label('')
                            ->content(static function (Get $get): string {
                                $count = self::buildCatalogQuery($get)->count();

                                return "Найдено скинов: {$count} (показывается до 200)";
                            }),

                        CheckboxList::make('selected_external_ids')
                            ->label('Выберите скины')
                            ->options(static function (Get $get): array {
                                return self::buildCatalogQuery($get)
                                    ->orderBy('name')
                                    ->limit(200)
                                    ->get()
                                    ->mapWithKeys(static fn (SkinCatalogItem $item): array => [
                                        $item->external_id => sprintf(
                                            '%s  [%s]  — %s ₽',
                                            $item->name,
                                            $item->rarity ?? '?',
                                            $item->market_price ? number_format((float) $item->market_price, 0, '.', ' ') : '?',
                                        ),
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(1)
                            ->gridDirection('row'),

                        Select::make('default_wear')
                            ->label('Износ для всех добавляемых скинов')
                            ->options(ItemWear::class)
                            ->required()
                            ->default(ItemWear::FT->value)
                            ->helperText('Каталог CS2 не хранит износ — выберите который нужен для этого уровня.'),

                        TextInput::make('price_multiplier')
                            ->label('Множитель цены')
                            ->numeric()
                            ->default('1.0')
                            ->minValue(0.01)
                            ->step('0.01')
                            ->suffix('×')
                            ->helperText('Итоговая цена = рыночная цена × множитель. 1.0 = без изменений, 0.9 = скидка 10%, 1.1 = наценка 10%.'),
                    ])
                    ->action(static function (array $data, RelationManager $livewire): void {
                        $externalIds = $data['selected_external_ids'] ?? [];

                        if (empty($externalIds)) {
                            Notification::make()->title('Не выбрано ни одного скина')->warning()->send();

                            return;
                        }

                        $wear = $data['default_wear'] instanceof ItemWear
                            ? $data['default_wear']
                            : ItemWear::from((string) $data['default_wear']);
                        $multiplier = (float) ($data['price_multiplier'] ?? 1.0);
                        $levelId = $livewire->getOwnerRecord()->id;

                        $catalogItems = SkinCatalogItem::query()
                            ->whereIn('external_id', $externalIds)
                            ->get();

                        $created = 0;
                        foreach ($catalogItems as $catalogItem) {
                            $rawPrice = $catalogItem->market_price !== null
                                ? (float) $catalogItem->market_price * $multiplier
                                : 0.0;

                            CaseItem::query()->create([
                                'case_level_id' => $levelId,
                                'skin_catalog_external_id' => $catalogItem->external_id,
                                'name' => $catalogItem->name,
                                'image_url' => $catalogItem->image_url,
                                'price' => round($rawPrice, 2),
                                'wear' => $wear,
                                'rarity' => $catalogItem->rarity !== null
                                    ? ItemRarity::tryFrom($catalogItem->rarity) ?? ItemRarity::MilSpec
                                    : ItemRarity::MilSpec,
                            ]);
                            $created++;
                        }

                        Notification::make()
                            ->title("Добавлено {$created} скинов в уровень")
                            ->success()
                            ->send();
                    })
                    ->modalWidth('4xl'),

                CreateAction::make()
                    ->label('Добавить один скин')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function buildCatalogQuery(Get $get): Builder
    {
        $query = SkinCatalogItem::query();

        $rarity = $get('filter_rarity');
        if ($rarity) {
            $query->where('rarity', $rarity);
        }

        $weapon = $get('filter_weapon');
        if ($weapon) {
            $query->where('weapon_name', $weapon);
        }

        $name = $get('filter_name');
        if ($name && strlen(trim($name)) >= 2) {
            $query->where('name', 'ilike', '%'.trim($name).'%');
        }

        $priceMax = $get('filter_price_max');
        if ($priceMax && is_numeric($priceMax) && (float) $priceMax > 0) {
            $query->where('market_price', '<=', (float) $priceMax);
        }

        return $query;
    }
}
