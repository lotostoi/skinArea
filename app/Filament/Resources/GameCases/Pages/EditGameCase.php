<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\Pages;

use App\Filament\Resources\GameCases\Actions\AdjustFundAction;
use App\Filament\Resources\GameCases\GameCaseResource;
use App\Filament\Resources\GameCases\Widgets\CaseFundWidget;
use App\Services\CaseEconomyValidator;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

class EditGameCase extends EditRecord
{
    protected static string $resource = GameCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AdjustFundAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CaseFundWidget::make(['record' => $this->record]),
        ];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $resolvedMessage = is_string($message) ? $message : 'Сохранение не выполнено. Проверьте настройки кейса.';

            $this->addError('data.is_active', $resolvedMessage);

            Notification::make()
                ->title('Сохранение не выполнено')
                ->body($resolvedMessage)
                ->danger()
                ->send();
        }
    }

    protected function beforeSave(): void
    {
        $data = $this->form->getState();

        if (! ((bool) ($data['is_active'] ?? false))) {
            return;
        }

        /** @var CaseEconomyValidator $validator */
        $validator = app(CaseEconomyValidator::class);

        try {
            $validator->validate($this->record, $this->record->levels()->get());
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $resolvedMessage = is_string($message) ? $message : 'Сначала завершите настройку уровней и шансов.';

            Notification::make()
                ->title('Кейс нельзя активировать')
                ->body($resolvedMessage)
                ->danger()
                ->send();

            $this->addError('data.is_active', $resolvedMessage);

            throw new Halt;
        }
    }
}
