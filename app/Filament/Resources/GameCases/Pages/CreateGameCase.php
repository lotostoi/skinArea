<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\Pages;

use App\Filament\Resources\GameCases\GameCaseResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

class CreateGameCase extends CreateRecord
{
    protected static string $resource = GameCaseResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_manual_admin_case'] = true;

        return $data;
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $resolvedMessage = is_string($message) ? $message : 'Создание не выполнено. Проверьте настройки кейса.';

            $this->addError('data.is_active', $resolvedMessage);

            Notification::make()
                ->title('Создание не выполнено')
                ->body($resolvedMessage)
                ->danger()
                ->send();
        }
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        if (! ((bool) ($data['is_active'] ?? false))) {
            return;
        }

        $message = 'Нельзя создать активный кейс без уровней. Сохраните кейс как черновик, добавьте уровни и затем активируйте.';

        Notification::make()
            ->title('Кейс нельзя активировать')
            ->body($message)
            ->danger()
            ->send();

        $this->addError('data.is_active', $message);

        throw new Halt;
    }
}
