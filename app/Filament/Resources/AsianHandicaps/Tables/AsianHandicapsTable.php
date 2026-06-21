<?php

namespace App\Filament\Resources\AsianHandicaps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AsianHandicapsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('matchGame.id')
                    ->label('ID матча')
                    ->sortable()
                    ->searchable(),
                /*TextColumn::make('matchGame')
                    ->label('Матч')
                    ->formatStateUsing(fn ($record) =>
                        $record->matchGame->homeTeam->name . ' vs ' . $record->matchGame->awayTeam->name
                    )
                    ->searchable(['matchGame.homeTeam.name', 'matchGame.awayTeam.name']),*/

                TextColumn::make('matchGame.homeTeam.name')
                    ->label('Хозяева')
                    ->searchable(),
                TextColumn::make('matchGame.awayTeam.name')
                    ->label('Гости')
                    ->searchable(),

                BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'success' => 'balanced',
                        'warning' => 'purchase',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'balanced' ? 'Равновесная' : 'Покупная'),
                TextColumn::make('home_handicap')
                    ->label('Фора 1')
                    ->sortable(),
                TextColumn::make('away_handicap')
                    ->label('Фора 2')
                    ->sortable(),
                TextColumn::make('home_odds')
                    ->label('Кэф 1')
                    ->sortable(),
                TextColumn::make('away_odds')
                    ->label('Кэф 2')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'balanced' => 'Равновесная',
                        'purchase' => 'Покупная',
                    ]),
                SelectFilter::make('match_game_id')
                    ->label('Матч')
                    ->relationship('matchGame', 'id')
                    ->searchable(),
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
