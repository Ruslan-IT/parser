<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MatchGame;
use App\Models\AsianHandicap;
use Illuminate\Support\Facades\Process;

class CollectAsianHandicaps extends Command
{
    protected $signature = 'ah:collect {--limit=100} {--match-id=}';
    protected $description = 'Collect Asian Handicap odds for matches';

    public function handle()
    {
        $query = MatchGame::doesntHave('asianHandicaps')
            ->whereNotNull('url');


        if ($this->option('match-id')) {
            $query->where('id', $this->option('match-id'));
        }

        $matches = $query->limit($this->option('limit'))->get();

        if ($matches->isEmpty()) {
            $this->info('Нет матчей для сбора AH.');
            return 0;
        }

        $this->info("Найдено матчей: " . $matches->count());

        $bar = $this->output->createProgressBar($matches->count());
        $bar->start();

        foreach ($matches as $match) {
            $this->collectForMatch($match);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Готово!');
        return 0;
    }

    private function collectForMatch($match)
    {
        $url = $match->url . '#ah';
        $this->info("\nОбработка-#ah: " . $url, 'v');

        $result = Process::path(base_path())->run([
            env('NODE_PATH', 'node'),
            base_path('parser.js'),
            $url
        ]);

        $output = $result->output();
        $error = $result->errorOutput();

        // Логируем сырой вывод
        \Log::info('Raw output from parser for match ID ' . $match->id, ['output' => $output, 'error' => $error]);

        if ($error) {
            $this->error("Ошибка для матча ID {$match->id}: " . $error);
            return;
        }

        $data = json_decode($output, true);
        \Log::info('New Raw data from parser for match ID '.$match->id, ['data' => $data]);

        \Log::info('PROVERKA AH allLines for match ID ' . $match->id, [
            'allLines' => $data[0]['ah']['allLines'] ?? null
        ]);





        if (!is_array($data) || empty($data)) {
            $this->warn("Нет данных для матча ID {$match->id}");
            return;
        }

        $matchData = $data[0] ?? null;
        if (!$matchData || !isset($matchData['ah'])) {
            $this->warn("Нет AH данных для матча ID {$match->id}");
            return;
        }

        $ah = $matchData['ah'];
        if (isset($ah['balanced'])) {
            AsianHandicap::updateOrCreate(
                ['match_game_id' => $match->id, 'type' => 'balanced'],
                [
                    'home_handicap' => $ah['balanced']['homeHandicap'],
                    'away_handicap' => $ah['balanced']['awayHandicap'],
                    'home_odds' => $ah['balanced']['homeOdds'],
                    'away_odds' => $ah['balanced']['awayOdds'],
                ]
            );
        }

        if (isset($ah['purchase'])) {
            AsianHandicap::updateOrCreate(
                ['match_game_id' => $match->id, 'type' => 'purchase'],
                [
                    'home_handicap' => $ah['purchase']['homeHandicap'],
                    'away_handicap' => $ah['purchase']['awayHandicap'],
                    'home_odds' => $ah['purchase']['homeOdds'],
                    'away_odds' => $ah['purchase']['awayOdds'],
                ]
            );
        }

        $this->info("Сохранено AH для матча ID {$match->id}");
    }
}
