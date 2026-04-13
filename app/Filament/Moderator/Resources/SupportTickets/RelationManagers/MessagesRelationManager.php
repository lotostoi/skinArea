<?php

declare(strict_types=1);

namespace App\Filament\Moderator\Resources\SupportTickets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Сообщения';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->label('Текст')
                    ->required()
                    ->maxLength(10000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('author.username')->label('Автор'),
                IconColumn::make('is_staff')->label('Сотрудник')->boolean(),
                TextColumn::make('body')->label('Сообщение')->wrap()->limit(200),
                TextColumn::make('created_at')->label('Время')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ответ поддержки')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['is_staff'] = true;

                        return $data;
                    }),
            ])
            ->defaultSort('created_at');
    }
}
