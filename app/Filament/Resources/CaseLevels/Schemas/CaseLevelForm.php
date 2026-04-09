<?php

namespace App\Filament\Resources\CaseLevels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CaseLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('case_id')
                    ->required()
                    ->numeric(),
                TextInput::make('level')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('chance')
                    ->required()
                    ->numeric(),
            ]);
    }
}
