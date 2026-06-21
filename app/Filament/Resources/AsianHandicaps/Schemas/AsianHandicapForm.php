<?php

namespace App\Filament\Resources\AsianHandicaps\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AsianHandicapForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('match_game_id')
                    ->label('Матч')
                    ->relationship('matchGame', 'id')
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->label('Тип')
                    ->options([
                        'balanced' => 'Равновесная',
                        'purchase' => 'Покупная',
                    ])
                    ->required(),
                TextInput::make('home_handicap')
                    ->label('Фора хозяев')
                    ->numeric()
                    ->required(),
                TextInput::make('away_handicap')
                    ->label('Фора гостей')
                    ->numeric()
                    ->required(),
                TextInput::make('home_odds')
                    ->label('Кэф хозяев')
                    ->numeric()
                    ->required(),
                TextInput::make('away_odds')
                    ->label('Кэф гостей')
                    ->numeric()
                    ->required(),
            ]);
    }
}
