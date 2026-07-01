<?php

namespace App\Filament\Resources\MatchPredictions\Pages;

use App\Filament\Resources\MatchPredictions\MatchPredictionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMatchPrediction extends EditRecord
{
    protected static string $resource = MatchPredictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
