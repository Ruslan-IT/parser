<?php

namespace App\Filament\Pages;

use App\Models\League;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Process;

use App\Models\Team;
use App\Models\MatchGame;
use Carbon\Carbon;

class Parser extends Page
{
    //protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';
    protected static ?string $navigationLabel = 'Парсер матчей';
    protected static ?string $title = 'Парсер данных BetExplorer';
    //protected static ?string $navigationGroup = 'Управление данными';

    protected string $view = 'filament.pages.parser';

    public $output = '';
    public $savedCount = 0;
    public $matchesList = [];
    public $url = ''; // новое свойство для URL

   /* public function runParser(){

        $result = Process::run('"C:\Program Files\nodejs\node.exe" ' . base_path('parser.js'));

        // нужно будет изменить путь

        $result = Process::run('node ' . base_path('parser.js'));


        $this->output = $result->output();

        $this->output = $result->errorOutput();

    }*/



   /* public function runParser(){

        $result = Process::path(base_path())
            ->env([
                'PLAYWRIGHT_BROWSERS_PATH' => '0',
            ])
            ->run('/usr/bin/node parser.js');

        $this->output =
            $result->output() . "\n\n" .
            $result->errorOutput();




    }*/



    public function runParser()
    {
        // Проверяем, что URL введён
        if (empty($this->url)) {
            $this->output = "❌ Пожалуйста, введите ссылку для парсинга.";
            return;
        }

        $nodePath = env('NODE_PATH', 'node');

        //$nodePath = 'C:\Program Files\nodejs\node.exe'; // или просто 'node' если в PATH
        $scriptPath = base_path('parser.js');

        // Передаём URL как третий аргумент
        $result = Process::path(base_path())
            ->env([
                'PLAYWRIGHT_BROWSERS_PATH' => 0,
            ])
            ->run([
                $nodePath,
                base_path('parser.js'),
                $this->url,
            ]);

        $output = $result->output();
        $errorOutput = $result->errorOutput();

        if (!empty($errorOutput)) {
            $this->output = "❌ Ошибка Node.js: " . $errorOutput;
            \Log::error($errorOutput);
            return;
        }

        $matches = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output = "❌ Ошибка JSON: " . json_last_error_msg();
            return;
        }

        $this->savedCount = $this->saveMatchesToDatabase($matches);
        $this->output = "✅ Успешно сохранено матчей: " . $this->savedCount;

        $this->matchesList = MatchGame::with(['league', 'homeTeam', 'awayTeam'])
            ->latest('match_date')
            ->take(10)
            ->get()
            ->toArray();

    }

    private function saveMatchesToDatabase(array $matches): int
    {
        $saved = 0;

        foreach ($matches as $match) {

            // Пропускаем, если нет нужных данных
            if (empty($match['homeTeam']) || empty($match['awayTeam'])) {
                continue;
            }

            // Лига
            $leagueName = $match['league'] ?? ($match['tournament'] ?? 'Unknown');
            $country = $match['country'] ?? null;
            $league = League::firstOrCreate(
                ['name' => $leagueName],
                ['country' => $country]
            );

            // Команды
            $homeTeam = Team::firstOrCreate(['name' => $match['homeTeam']]);
            $awayTeam = Team::firstOrCreate(['name' => $match['awayTeam']]);

            // Дата матча
            $matchDate = null;
            if (!empty($match['date'])) {
                try {
                    $matchDate = \Carbon\Carbon::createFromFormat('d.m.Y', $match['date'])->toDateTimeString();
                } catch (\Exception $e) {}
            }

            // Уникальный идентификатор матча (если есть matchId – используем его, иначе составляем из даты и команд)
            $betexplorerId = $match['matchId'] ?? md5($match['date'] . $match['homeTeam'] . $match['awayTeam']);

            // Сохраняем матч
            MatchGame::updateOrCreate(
                ['betexplorer_id' => $betexplorerId],
                [
                    'league_id' => $league->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'match_date' => $matchDate,
                    'home_score' => $match['homeScore'],
                    'away_score' => $match['awayScore'],
                    'url' => $match['matchId'] ? "https://www.betexplorer.com/match/{$match['matchId']}/" : '',
                    'odds_json' => json_encode($match['odds']),
                ]
            );

            $saved++;
        }

        return $saved;
    }




}


