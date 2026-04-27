<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseLevels\Pages;

use App\Filament\Resources\CaseLevels\CaseLevelResource;
use App\Filament\Resources\GameCases\GameCaseResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCaseLevel extends EditRecord
{
    protected static string $resource = CaseLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_case')
                ->label('К кейсу')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => GameCaseResource::getUrl('edit', [
                    'record' => $this->record->case_id,
                ])),

            Action::make('add_skins')
                ->label('Добавить скины из каталога')
                ->icon('heroicon-o-squares-plus')
                ->color('primary')
                ->url(fn (): string => CaseLevelResource::getUrl('add-skins', [
                    'record' => $this->record->getKey(),
                ])),

            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        $levelName = $this->record->name ?? 'Уровень';

        return "Уровень: {$levelName} — призы";
    }
}
