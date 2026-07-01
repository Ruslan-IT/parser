<?php

namespace App\Filament\Resources\CriteriaValues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CriteriaValuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matchGame.homeTeam.name')->label('Хозяева'),
                TextColumn::make('matchGame.awayTeam.name')->label('Гости'),
                TextColumn::make('criteria_1')->label('Кр1'),
                TextColumn::make('criteria_2')->label('Кр2'),
                TextColumn::make('criteria_3')->label('Кр3'),
                TextColumn::make('criteria_4')->label('Кр4'),
                TextColumn::make('handicap_balanced_home')->label('Фора (равн) дом')->numeric(5),
                TextColumn::make('handicap_balanced_away')->label('Фора (равн) гости')->numeric(5),
                TextColumn::make('handicap_purchase_home')->label('Фора (покуп) дом')->numeric(5),
                TextColumn::make('handicap_purchase_away')->label('Фора (покуп) гости')->numeric(5),
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
