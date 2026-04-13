<?php

declare(strict_types=1);

namespace App\Filament\Moderator\Resources\SupportTickets;

use App\Filament\Moderator\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Filament\Moderator\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Moderator\Resources\SupportTickets\RelationManagers\MessagesRelationManager;
use App\Filament\Moderator\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Filament\Moderator\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Техподдержка';

    protected static ?string $modelLabel = 'тикет';

    protected static ?string $pluralModelLabel = 'Тикеты техподдержки';

    public static function form(Schema $schema): Schema
    {
        return SupportTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'edit' => EditSupportTicket::route('/{record}/edit'),
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
        return parent::getEloquentQuery()->with('user');
    }
}
