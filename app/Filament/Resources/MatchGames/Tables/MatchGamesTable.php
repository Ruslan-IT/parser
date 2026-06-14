<?php

namespace App\Filament\Resources\MatchGames\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MatchGamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('league.name')
                    ->label('Лига')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('homeTeam.name')
                    ->label('Хозяева')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('awayTeam.name')
                    ->label('Гости')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('home_score')
                    ->label('Счёт (хозяева)')
                    ->sortable(),
                TextColumn::make('away_score')
                    ->label('Счёт (гости)')
                    ->sortable(),
                TextColumn::make('match_date')
                    ->label('Дата матча')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('odds_json')
                    ->label('Коэффициенты (1X2)')
                    ->formatStateUsing(fn ($state) => $state ? implode(' | ', json_decode($state, true)) : '—'),
                TextColumn::make('created_at')
                    ->label('Дата импорта')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('league_id')
                    ->label('Лига')
                    ->relationship('league', 'name'),
                SelectFilter::make('home_team_id')
                    ->label('Команда хозяев')
                    ->relationship('homeTeam', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
