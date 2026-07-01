<?php

namespace App\Filament\Resources\CriteriaValues;

use App\Filament\Resources\CriteriaValues\Pages\CreateCriteriaValue;
use App\Filament\Resources\CriteriaValues\Pages\EditCriteriaValue;
use App\Filament\Resources\CriteriaValues\Pages\ListCriteriaValues;
use App\Filament\Resources\CriteriaValues\Schemas\CriteriaValueForm;
use App\Filament\Resources\CriteriaValues\Tables\CriteriaValuesTable;
use App\Models\CriteriaValue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CriteriaValueResource extends Resource
{
    protected static ?string $model = CriteriaValue::class;


    protected static ?string $navigationLabel = 'Расчёт критериев ';
    protected static ?string $pluralLabel = 'Расчёт критериев ';
    protected static ?string $label = 'Расчёт критериев ';




    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CriteriaValueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CriteriaValuesTable::configure($table);
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
            'index' => ListCriteriaValues::route('/'),
            'create' => CreateCriteriaValue::route('/create'),
            'edit' => EditCriteriaValue::route('/{record}/edit'),
        ];
    }
}
