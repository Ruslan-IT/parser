<?php

namespace App\Filament\Resources\TeamSeasonStats\Tables;

use App\Models\TeamSeasonStat;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeamSeasonStatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('team.name')
                    ->label('Команда')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('league.name')
                    ->label('Лига')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('season')
                    ->label('Сезон')
                    ->sortable(),
                TextColumn::make('matches_total')
                    ->label('Матчи')
                    ->sortable(),
                TextColumn::make('points_total')
                    ->label('Очки')
                    ->sortable(),
                TextColumn::make('goals_scored_total')
                    ->label('Забито')
                    ->sortable(),
                TextColumn::make('goals_conceded_total')
                    ->label('Пропущено')
                    ->sortable(),
                TextColumn::make('goals_diff_total')
                    ->label('Разница')
                    ->sortable(),
                TextColumn::make('points_last5')
                    ->label('Форма (5 матчей)')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('league_id')
                    ->label('Лига')
                    ->relationship('league', 'name'),
                SelectFilter::make('season')
                    ->label('Сезон')
                    ->options(function () {
                        return TeamSeasonStat::distinct()->pluck('season', 'season')->toArray();
                    }),
            ])
            ->filters([
                //
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
