<?php

namespace App\Filament\Resources\WithdrawalRequests\Schemas;

use App\Enums\WithdrawalRequestStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WithdrawalRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(WithdrawalRequestStatus::class)
                    ->default('pending')
                    ->required(),
                Textarea::make('admin_comment')
                    ->columnSpanFull(),
                DateTimePicker::make('processed_at'),
                TextInput::make('processed_by')
                    ->numeric(),
            ]);
    }
}
