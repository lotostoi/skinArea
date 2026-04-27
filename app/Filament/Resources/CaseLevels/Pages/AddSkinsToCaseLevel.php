<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseLevels\Pages;

use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Filament\Resources\CaseLevels\CaseLevelResource;
use App\Filament\Resources\GameCases\GameCaseResource;
use App\Models\CaseItem;
use App\Models\CaseLevel;
use App\Models\SkinCatalogItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class AddSkinsToCaseLevel extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = CaseLevelResource::class;

    // нестатическое — как в родительском Filament\Pages\Page
    protected string $view = 'filament.resources.case-levels.pages.add-skins-to-case-level';

    public int $levelId;

    public ?CaseLevel $level = null;

    public function mount(int $record): void
    {
        $this->levelId = $record;
        $this->level = CaseLevel::with('gameCase')->findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Каталог скинов — добавить в уровень «'.($this->level?->name ?? '').'»';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_level')
                ->label('← К уровню')
                ->color('gray')
                ->url(fn (): string => CaseLevelResource::getUrl('edit', ['record' => $this->levelId])),

            Action::make('back_to_case')
                ->label('← К кейсу')
                ->color('gray')
                ->url(fn (): string => GameCaseResource::getUrl('edit', [
                    'record' => $this->level?->case_id ?? 0,
                ])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SkinCatalogItem::query())
            ->defaultSort('name')
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Split::make([
                    Tables\Columns\ImageColumn::make('image_url')
                        ->label('')
                        ->getStateUsing(static fn (SkinCatalogItem $record): ?string => $record->image_url)
                        ->square()
                        ->size(80)
                        ->grow(false)
                        ->extraImgAttributes([
                            'loading' => 'lazy',
                            'style' => 'object-fit:contain;background:#1a1a24;border-radius:8px;padding:6px',
                        ]),

                    Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->label('Название')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->size('sm')
                            ->wrap(),

                        Tables\Columns\TextColumn::make('weapon_name')
                            ->label('Оружие')
                            ->searchable()
                            ->sortable()
                            ->badge()
                            ->color('gray')
                            ->placeholder('—'),

                        Tables\Columns\TextColumn::make('rarity')
                            ->label('Редкость')
                            ->badge()
                            ->formatStateUsing(static fn (?string $state): string => $state
                                ? (ItemRarity::tryFrom($state)?->getLabel() ?? $state)
                                : '—')
                            ->color(static fn (?string $state): string => match ($state) {
                                'consumer_grade' => 'gray',
                                'industrial_grade' => 'info',
                                'mil_spec' => 'primary',
                                'restricted' => 'warning',
                                'classified', 'covert' => 'danger',
                                'contraband' => 'warning',
                                default => 'gray',
                            })
                            ->sortable(),

                        Tables\Columns\TextColumn::make('market_price')
                            ->label('Цена, $')
                            ->numeric(decimalPlaces: 2)
                            ->prefix('$')
                            ->sortable()
                            ->placeholder('—')
                            ->weight('bold')
                            ->color('primary'),
                    ])->space(1),
                ]),
            ])
            ->filters([
                SelectFilter::make('rarity')
                    ->label('Редкость')
                    ->options(ItemRarity::class),

                SelectFilter::make('weapon_name')
                    ->label('Оружие')
                    ->options(static fn (): array => SkinCatalogItem::query()
                        ->whereNotNull('weapon_name')
                        ->where('weapon_name', '!=', '')
                        ->distinct()
                        ->orderBy('weapon_name')
                        ->pluck('weapon_name', 'weapon_name')
                        ->all())
                    ->searchable(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('add_to_level')
                    ->label('Добавить в уровень')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->form(function (SkinCatalogItem $record): array {
                        $defaultPrice = $record->market_price !== null
                            ? (string) $record->market_price
                            : '0';

                        return [
                            Select::make('wear')
                                ->label('Износ для добавляемых скинов')
                                ->options(ItemWear::class)
                                ->required()
                                ->default(ItemWear::FT->value)
                                ->helperText('Каталог CS2 не хранит износ — выберите нужный для этого уровня.'),

                            TextInput::make('price')
                                ->label('Цена в кейсе, ₽')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step('0.01')
                                ->default($defaultPrice)
                                ->validationAttribute('цена')
                                ->validationMessages([
                                    'required' => 'Укажите цену.',
                                    'numeric' => 'Цена должна быть числом.',
                                    'min' => 'Цена не может быть отрицательной.',
                                ])
                                ->helperText('По умолчанию подставляется цена из каталога (market_price), если в каталоге нет — 0. Можно изменить перед сохранением.'),
                        ];
                    })
                    ->action(function (SkinCatalogItem $record, array $data): void {
                        $wear = $data['wear'] instanceof ItemWear
                            ? $data['wear']
                            : ItemWear::from((string) $data['wear']);

                        $price = max(0.0, (float) ($data['price'] ?? 0));

                        CaseItem::query()->create([
                            'case_level_id' => $this->levelId,
                            'skin_catalog_external_id' => $record->external_id,
                            'name' => $record->name,
                            'image_url' => $record->image_url,
                            'price' => round($price, 2),
                            'wear' => $wear,
                            'rarity' => $record->rarity !== null
                                ? ItemRarity::tryFrom($record->rarity) ?? ItemRarity::MilSpec
                                : ItemRarity::MilSpec,
                        ]);

                        Notification::make()
                            ->title("Скин «{$record->name}» добавлен в уровень «{$this->level?->name}»")
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Добавить скин в уровень')
                    ->modalSubmitActionLabel('Добавить в уровень'),
            ])
            ->paginated([25, 50, 100]);
    }
}
