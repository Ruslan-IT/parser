<?php

namespace App\Filament\Resources\SavedUrlSets;

use App\Filament\Resources\SavedUrlSets\Pages\CreateSavedUrlSet;
use App\Filament\Resources\SavedUrlSets\Pages\EditSavedUrlSet;
use App\Filament\Resources\SavedUrlSets\Pages\ListSavedUrlSets;
use App\Filament\Resources\SavedUrlSets\Schemas\SavedUrlSetForm;
use App\Filament\Resources\SavedUrlSets\Tables\SavedUrlSetsTable;
use App\Models\SavedUrlSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SavedUrlSetResource extends Resource
{
    protected static ?string $model = SavedUrlSet::class;

    protected static ?string $navigationLabel = 'Ссылки для парсера';
    protected static ?string $pluralLabel = 'Ссылки для парсера';
    protected static ?string $label = 'Ссылки для парсера';


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SavedUrlSetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SavedUrlSetsTable::configure($table);
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
            'index' => ListSavedUrlSets::route('/'),
            'create' => CreateSavedUrlSet::route('/create'),
            'edit' => EditSavedUrlSet::route('/{record}/edit'),
        ];
    }
}
