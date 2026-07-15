<?php

namespace App\Jobs;

use App\Models\MatchGame;
use App\Console\Commands\CollectAsianHandicaps;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class CollectAsianHandicapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 минут на один матч (может быть достаточно)
    public $tries = 3;

    protected $matchId;

    public function __construct($matchId)
    {
        $this->matchId = $matchId;
    }

    public function handle()
    {
        $match = MatchGame::find($this->matchId);
        if (!$match) return;

        // Запускаем сбор AH для одного матча (можно использовать существующую логику)
        // Если у вас есть отдельный метод для сбора одной форы, используйте его.
        // В качестве простого решения – вызываем команду с конкретным ID:
        Artisan::call('ah:collect', [
            '--match-id' => $this->matchId, // нужно добавить такой параметр в команду
        ]);
    }
}
