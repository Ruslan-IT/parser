<?php

namespace App\Filament\Resources\TeamSeasonStats;

use App\Filament\Resources\TeamSeasonStats\Pages\CreateTeamSeasonStat;
use App\Filament\Resources\TeamSeasonStats\Pages\EditTeamSeasonStat;
use App\Filament\Resources\TeamSeasonStats\Pages\ListTeamSeasonStats;
use App\Filament\Resources\TeamSeasonStats\Schemas\TeamSeasonStatForm;
use App\Filament\Resources\TeamSeasonStats\Tables\TeamSeasonStatsTable;
use App\Models\TeamSeasonStat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TeamSeasonStatResource extends Resource
{
    protected static ?string $model = TeamSeasonStat::class;


    protected static ?string $navigationLabel = 'Статистика команд';
    protected static ?string $pluralLabel = 'Статистика команд';
    protected static ?string $label = 'Статистика команд';


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TeamSeasonStatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamSeasonStatsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamSeasonStats::route('/'),
            'create' => CreateTeamSeasonStat::route('/create'),
            'edit' => EditTeamSeasonStat::route('/{record}/edit'),
        ];
    }
}
