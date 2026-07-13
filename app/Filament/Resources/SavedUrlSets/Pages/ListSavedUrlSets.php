<?php

namespace App\Filament\Resources\SavedUrlSets\Pages;

use App\Filament\Resources\SavedUrlSets\SavedUrlSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavedUrlSets extends ListRecords
{
    protected static string $resource = SavedUrlSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
