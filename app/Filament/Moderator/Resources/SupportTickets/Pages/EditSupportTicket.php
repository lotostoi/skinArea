<?php

declare(strict_types=1);

namespace App\Filament\Moderator\Resources\SupportTickets\Pages;

use App\Filament\Moderator\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('liftSupportMute')
                ->label('Снять бан в чате')
                ->visible(function (): bool {
                    $record = $this->getRecord();
                    if (! $record instanceof SupportTicket) {
                        return false;
                    }
                    $record->loadMissing('user');

                    return $record->user?->support_muted_until !== null;
                })
                ->action(function (): void {
                    /** @var SupportTicket $record */
                    $record = $this->getRecord();
                    $record->loadMissing('user');
                    $record->user?->update(['support_muted_until' => null]);
                    Notification::make()->title('Ограничение чата снято')->success()->send();
                }),
            Action::make('clearPasswordResetTokens')
                ->label('Сбросить токены сброса пароля')
                ->visible(function (): bool {
                    $record = $this->getRecord();
                    if (! $record instanceof SupportTicket) {
                        return false;
                    }
                    $record->loadMissing('user');

                    return filled($record->user?->email);
                })
                ->requiresConfirmation()
                ->modalDescription('Пользователю нужно будет запросить ссылку сброса пароля заново.')
                ->action(function (): void {
                    /** @var SupportTicket $record */
                    $record = $this->getRecord();
                    $record->loadMissing('user');
                    $email = $record->user?->email;
                    if ($email !== null) {
                        DB::table('password_reset_tokens')->where('email', $email)->delete();
                    }
                    Notification::make()->title('Токены сброса пароля удалены')->success()->send();
                }),
        ];
    }
}
