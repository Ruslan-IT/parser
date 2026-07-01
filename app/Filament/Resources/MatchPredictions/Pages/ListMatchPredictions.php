<?php

namespace App\Filament\Resources\MatchPredictions\Pages;

use App\Filament\Resources\MatchPredictions\MatchPredictionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMatchPredictions extends ListRecords
{
    protected static string $resource = MatchPredictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
