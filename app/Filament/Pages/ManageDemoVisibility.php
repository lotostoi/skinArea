<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageDemoVisibility extends Page
{
    protected static ?string $title = 'Демо-данные';

    protected static ?string $navigationLabel = 'Демо-данные';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static string|UnitEnum|null $navigationGroup = 'Система';

    protected static ?int $navigationSort = 90;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'show_demo_data' => SiteSetting::showDemoData(),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Показ демо-данных')
                    ->description(
                        'Данные из DemoSeeder (лоты демо-продавцов, кейсы без флага ручного создания, сделки с демолотами). При выключении на витрине остаются только лоты реальных продавцов и кейсы, созданные в админке (флаг ручного кейса). Удаление демо из БД — команда php artisan demo:wipe.',
                    )
                    ->schema([
                        Toggle::make('show_demo_data')
                            ->label('Показывать демо-данные')
                            ->helperText('Выключите на продакшене, чтобы витрина показывала только реальные лоты и кейсы.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->key('form-actions'),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Сохранить')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        SiteSetting::setShowDemoData((bool) ($state['show_demo_data'] ?? false));

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }
}
