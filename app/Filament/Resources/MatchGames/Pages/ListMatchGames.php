<?php

namespace App\Filament\Resources\MatchGames\Pages;


use App\Filament\Resources\MatchGames\MatchGameResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use App\Models\MatchGame;
use App\Models\League;
use App\Models\Team;

class ListMatchGames extends ListRecords
{
    protected static string $resource = MatchGameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearAllData')
                ->label('Очистить все данные')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Удаление всех данных')
                ->modalDescription('Будут удалены все матчи, лиги и команды. Это действие необратимо. Вы уверены?')
                ->modalSubmitActionLabel('Да, удалить всё')
                ->action(function () {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                    MatchGame::truncate();
                    League::truncate();
                    Team::truncate();
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');

                    Notification::make()
                        ->title('Все данные удалены')
                        ->success()
                        ->send();
                }),
        ];
    }
}
