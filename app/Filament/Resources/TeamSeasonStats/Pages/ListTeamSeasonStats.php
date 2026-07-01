<?php

namespace App\Filament\Resources\TeamSeasonStats\Pages;

use App\Filament\Resources\TeamSeasonStats\TeamSeasonStatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeamSeasonStats extends ListRecords
{
    protected static string $resource = TeamSeasonStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
