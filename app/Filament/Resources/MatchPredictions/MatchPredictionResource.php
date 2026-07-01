<?php

namespace App\Filament\Resources\MatchPredictions;

use App\Filament\Resources\MatchPredictions\Pages\CreateMatchPrediction;
use App\Filament\Resources\MatchPredictions\Pages\EditMatchPrediction;
use App\Filament\Resources\MatchPredictions\Pages\ListMatchPredictions;
use App\Filament\Resources\MatchPredictions\Schemas\MatchPredictionForm;
use App\Filament\Resources\MatchPredictions\Tables\MatchPredictionsTable;
use App\Models\MatchPrediction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MatchPredictionResource extends Resource
{
    protected static ?string $model = MatchPrediction::class;


    protected static ?string $navigationLabel = 'Расчёт вероятностей и эффективностей';
    protected static ?string $pluralLabel = 'Расчёт вероятностей и эффективностей';
    protected static ?string $label = 'Расчёт вероятностей и эффективностей';



    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MatchPredictionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MatchPredictionsTable::configure($table);
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
            'index' => ListMatchPredictions::route('/'),
            'create' => CreateMatchPrediction::route('/create'),
            'edit' => EditMatchPrediction::route('/{record}/edit'),
        ];
    }
}
