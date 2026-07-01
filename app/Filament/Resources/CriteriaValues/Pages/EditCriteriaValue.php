<?php

namespace App\Filament\Resources\CriteriaValues\Pages;

use App\Filament\Resources\CriteriaValues\CriteriaValueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCriteriaValue extends EditRecord
{
    protected static string $resource = CriteriaValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
