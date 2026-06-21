<?php

namespace App\Filament\Resources\AsianHandicaps\Pages;

use App\Filament\Resources\AsianHandicaps\AsianHandicapResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAsianHandicap extends EditRecord
{
    protected static string $resource = AsianHandicapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
