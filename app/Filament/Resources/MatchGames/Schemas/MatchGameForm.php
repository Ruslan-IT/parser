<?php

namespace App\Filament\Resources\MatchGames\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MatchGameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('league_id')
                    ->label('Лига')
                    ->relationship('league', 'name')
                    ->required(),
                Select::make('home_team_id')
                    ->label('Хозяева')
                    ->relationship('homeTeam', 'name')
                    ->required(),
                Select::make('away_team_id')
                    ->label('Гости')
                    ->relationship('awayTeam', 'name')
                    ->required(),
                DateTimePicker::make('match_date')
                    ->label('Дата матча'),
                TextInput::make('home_score')
                    ->label('Голов хозяев')
                    ->numeric(),
                TextInput::make('away_score')
                    ->label('Голов гостей')
                    ->numeric(),
                Textarea::make('odds_json')
                    ->label('Коэффициенты (1X2)')
                    ->helperText('Формат JSON массива, например ["2.10", "3.20", "3.50"]'),
                TextInput::make('betexplorer_id')
                    ->label('ID на BetExplorer')
                    ->required(),
                TextInput::make('url')
                    ->label('Ссылка')
                    ->url(),
            ]);
    }
}
