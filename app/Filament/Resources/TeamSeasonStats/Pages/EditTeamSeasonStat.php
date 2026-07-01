<?php

namespace App\Filament\Resources\TeamSeasonStats\Pages;

use App\Filament\Resources\TeamSeasonStats\TeamSeasonStatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeamSeasonStat extends EditRecord
{
    protected static string $resource = TeamSeasonStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
