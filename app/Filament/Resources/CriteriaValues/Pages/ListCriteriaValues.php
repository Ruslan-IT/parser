<?php

namespace App\Filament\Resources\CriteriaValues\Pages;

use App\Filament\Resources\CriteriaValues\CriteriaValueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCriteriaValues extends ListRecords
{
    protected static string $resource = CriteriaValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
