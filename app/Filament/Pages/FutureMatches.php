<?php

namespace App\Filament\Pages;

use App\Models\MatchGame;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FutureMatches extends Page implements HasTable
{
    use InteractsWithTable;


    protected static ?string $navigationLabel = '📅 Будущие матчи';
    protected static ?string $title = 'Будущие матчи и прогнозы';


    protected string $view = 'filament.pages.future-matches';

    public function getTableQuery(): Builder
    {
        return MatchGame::query()
            ->where('match_status', 'scheduled')
            ->with(['league', 'homeTeam', 'awayTeam', 'averagePrediction']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('match_date')
                    ->label('Дата')
                    ->date('d.m.Y H:i')
                    ->sortable(),
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
                // Коэффициенты 1X2
                TextColumn::make('odd_home')
                    ->label('Кэф 1')
                    ->sortable(),
                TextColumn::make('odd_draw')
                    ->label('Кэф X')
                    ->sortable(),
                TextColumn::make('odd_away')
                    ->label('Кэф 2')
                    ->sortable(),
                // Средние вероятности
                TextColumn::make('averagePrediction.prob_home')
                    ->label('P1 (сред.)')
                    ->formatStateUsing(fn($state) => $state !== null ? round($state * 100, 1).'%' : '—'),
                TextColumn::make('averagePrediction.prob_draw')
                    ->label('PX (сред.)')
                    ->formatStateUsing(fn($state) => $state !== null ? round($state * 100, 1).'%' : '—'),
                TextColumn::make('averagePrediction.prob_away')
                    ->label('P2 (сред.)')
                    ->formatStateUsing(fn($state) => $state !== null ? round($state * 100, 1).'%' : '—'),
                // Средние эффективности
                TextColumn::make('averagePrediction.eff_home')
                    ->label('E1 (сред.)')
                    ->formatStateUsing(fn($state) => $state !== null ? round($state, 3) : '—'),
                TextColumn::make('averagePrediction.eff_draw')
                    ->label('EX (сред.)')
                    ->formatStateUsing(fn($state) => $state !== null ? round($state, 3) : '—'),
                TextColumn::make('averagePrediction.eff_away')
                    ->label('E2 (сред.)')
                    ->formatStateUsing(fn($state) => $state !== null ? round($state, 3) : '—'),
            ])
            ->filters([
                SelectFilter::make('league_id')
                    ->label('Лига')
                    ->relationship('league', 'name'),
                Filter::make('match_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('С даты'),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('По дату'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('match_date', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('match_date', '<=', $date));
                    }),
            ])
            ->defaultSort('match_date', 'asc');
    }
}
