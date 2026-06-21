<?php

namespace App\Filament\Resources\MatchGames;

use App\Filament\Resources\MatchGames\Pages\CreateMatchGame;
use App\Filament\Resources\MatchGames\Pages\EditMatchGame;
use App\Filament\Resources\MatchGames\Pages\ListMatchGames;
use App\Filament\Resources\MatchGames\Schemas\MatchGameForm;
use App\Filament\Resources\MatchGames\Tables\MatchGamesTable;
use App\Models\League;
use App\Models\MatchGame;
use App\Models\Team;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class MatchGameResource extends Resource
{
    protected static ?string $model = MatchGame::class;

    // Русское название ресурса
    protected static ?string $navigationLabel = 'Матчи';
    protected static ?string $pluralLabel = 'Матчи';
    protected static ?string $label = 'Матч';


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MatchGameForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MatchGamesTable::configure($table);

    }

    //


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMatchGames::route('/'),
            'create' => CreateMatchGame::route('/create'),
            'edit' => EditMatchGame::route('/{record}/edit'),
        ];
    }
}
