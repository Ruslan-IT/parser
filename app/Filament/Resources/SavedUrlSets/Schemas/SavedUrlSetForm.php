<?php

namespace App\Filament\Resources\SavedUrlSets\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SavedUrlSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название набора')
                    ->required()
                    ->maxLength(255),

                Textarea::make('urls')
                    ->label('Ссылки (каждая с новой строки)')
                    ->required()
                    ->rows(25)
                    ->helperText('Вставляйте ссылки на турниры, каждая с новой строки.')
                    ->columnSpanFull(),
            ]);
    }
}
