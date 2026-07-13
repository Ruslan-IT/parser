<?php

namespace App\Filament\Resources\MatchPredictions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MatchPredictionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('matchGame.homeTeam.name')
                    ->label('Хозяева')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('matchGame.awayTeam.name')
                    ->label('Гости')
                    ->searchable()
                    ->sortable(),

                // Выводим тип записи (критерий или среднее)
                BadgeColumn::make('criteria_id')
                    ->label('Критерий')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->is_average) {
                            return 'Среднее';
                        }
                        return 'Кр' . $state;
                    })
                    ->colors([
                        'primary' => 'Среднее',
                        'success' => fn($state) => $state == 1,
                        'warning' => fn($state) => $state == 2,
                        'danger' => fn($state) => $state == 3,
                        'info' => fn($state) => $state == 4,
                        'secondary' => fn($state) => $state >= 5,
                    ]),

                // Категория
                TextColumn::make('category.name')
                    ->label('Категория')
                    ->default('—'),

                // Вероятности
                /*TextColumn::make('prob_home')
                    ->label('P1 (%)')
                    ->formatStateUsing(fn($state) => $state ? number_format($state * 100, 1) . '%' : '—')
                    ->alignCenter(),*/

               /* TextColumn::make('prob_draw')
                    ->label('PX (%)')
                    ->formatStateUsing(fn($state) => $state ? number_format($state * 100, 1) . '%' : '—')
                    ->alignCenter(),*/

                /*TextColumn::make('prob_away')
                    ->label('P2 (%)')
                    ->formatStateUsing(fn($state) => $state ? number_format($state * 100, 1) . '%' : '—')
                    ->alignCenter(),*/

                // Эффективности
                /*TextColumn::make('eff_home')
                    ->label('E1')
                    ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 3) : '—')
                    ->alignCenter()
                    ->color(fn($state) => $state > 1 ? 'success' : ($state < 1 ? 'danger' : null)),*/

                /*TextColumn::make('eff_draw')
                    ->label('EX')
                    ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 3) : '—')
                    ->alignCenter()
                    ->color(fn($state) => $state > 1 ? 'success' : ($state < 1 ? 'danger' : null)),*/

                /*TextColumn::make('eff_away')
                    ->label('E2')
                    ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 3) : '—')
                    ->alignCenter()
                    ->color(fn($state) => $state > 1 ? 'success' : ($state < 1 ? 'danger' : null)),*/

                // Дополнительно: информация о матче
                TextColumn::make('matchGame.match_date')
                    ->label('Дата матча')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('criteria_id')
                    ->label('Критерий')
                    ->options([
                        '' => 'Все',
                        '1' => 'Кр1',
                        '2' => 'Кр2',
                        '3' => 'Кр3',
                        '4' => 'Кр4',
                        '5' => 'Кр5',
                        '6' => 'Кр6 (Пуассон)',
                    ])
                    ->placeholder('Все критерии'),
                SelectFilter::make('is_average')
                    ->label('Тип прогноза')
                    ->options([
                        '' => 'Все',
                        '0' => 'По критериям',
                        '1' => 'Среднее',
                    ]),
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
