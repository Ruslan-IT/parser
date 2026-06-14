<?php

namespace App\Filament\Resources\MatchGames\Pages;

use App\Filament\Resources\MatchGames\MatchGameResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMatchGame extends EditRecord
{
    protected static string $resource = MatchGameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
