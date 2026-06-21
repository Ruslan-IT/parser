<?php

namespace App\Filament\Resources\AsianHandicaps\Pages;

use App\Filament\Resources\AsianHandicaps\AsianHandicapResource;
use App\Models\AsianHandicap;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsianHandicaps extends ListRecords
{
    protected static string $resource = AsianHandicapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('truncate')
                ->label('Очистить таблицу')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    AsianHandicap::query()->delete();
                }),
        ];
    }
}
