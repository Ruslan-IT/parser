<?php

namespace App\Filament\Resources\SavedUrlSets\Pages;

use App\Filament\Resources\SavedUrlSets\SavedUrlSetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSavedUrlSet extends EditRecord
{
    protected static string $resource = SavedUrlSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
