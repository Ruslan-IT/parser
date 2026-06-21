<?php

namespace App\Filament\Resources\AsianHandicaps\Pages;

use App\Filament\Resources\AsianHandicaps\AsianHandicapResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsianHandicaps extends ListRecords
{
    protected static string $resource = AsianHandicapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
