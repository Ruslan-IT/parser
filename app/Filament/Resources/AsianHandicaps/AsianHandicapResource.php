<?php

namespace App\Filament\Resources\AsianHandicaps;

use App\Filament\Resources\AsianHandicaps\Pages\CreateAsianHandicap;
use App\Filament\Resources\AsianHandicaps\Pages\EditAsianHandicap;
use App\Filament\Resources\AsianHandicaps\Pages\ListAsianHandicaps;
use App\Filament\Resources\AsianHandicaps\Schemas\AsianHandicapForm;
use App\Filament\Resources\AsianHandicaps\Tables\AsianHandicapsTable;
use App\Models\AsianHandicap;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AsianHandicapResource extends Resource
{
    protected static ?string $model = AsianHandicap::class;


    protected static ?string $navigationLabel = 'Азиатские форы';
    protected static ?string $pluralLabel = 'Азиатские форы';


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AsianHandicapForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsianHandicapsTable::configure($table);
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
            'index' => ListAsianHandicaps::route('/'),
            'create' => CreateAsianHandicap::route('/create'),
            'edit' => EditAsianHandicap::route('/{record}/edit'),
        ];
    }
}
