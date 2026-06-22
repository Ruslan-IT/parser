<?php

namespace App\Filament\Pages;

use App\Console\Commands\CollectAsianHandicaps;
use App\Jobs\CollectAsianHandicapsJob;
use App\Models\AsianHandicap;
use App\Models\League;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
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
    public $urls = ''; // новое свойство для URL

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
        if (empty($this->urls)) {
            $this->output = "❌ Пожалуйста, введите хотя бы одну ссылку.";
            return;
        }

        // Разбиваем по строкам, удаляем пустые
        $urlList = array_filter(array_map('trim', explode("\n", $this->urls)));
        if (empty($urlList)) {
            $this->output = "❌ Нет корректных ссылок.";
            return;
        }



        $nodePath = env('NODE_PATH', 'node');
        $scriptPath = base_path('parser.js');




        $allMatches = [];

        foreach ($urlList as $url) {
            $this->output = "⏳ Парсинг: $url ...";
            // Вызов парсера для одного URL
            $result = Process::path(base_path())->run([$nodePath, $scriptPath, $url]);

            $output = $result->output();
            $errorOutput = $result->errorOutput();

            if (!empty($errorOutput)) {
                $this->output = "❌ Ошибка при парсинге $url: " . $errorOutput;
                \Log::error($errorOutput);
                continue; // пропускаем этот URL, но продолжаем со следующими
            }

            $matches = json_decode($output, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output = "❌ Ошибка JSON для $url: " . json_last_error_msg();
                continue;
            }

            if (is_array($matches)) {
                $allMatches = array_merge($allMatches, $matches);
            }
        }

        if (empty($allMatches)) {
            $this->output = "❌ Не удалось получить ни одного матча.";
            return;
        }

        // Сохраняем все собранные матчи
        $this->savedCount = $this->saveMatchesToDatabase($allMatches);

        //CollectAsianHandicapsJob::dispatch();


        $this->output = "✅ Успешно сохранено матчей: " . $this->savedCount;

        // Обновляем список последних матчей для отображения (опционально)
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
            if (empty($match['homeTeam']) || empty($match['awayTeam'])) {
                continue;
            }

            //dd($match);

            $oddHome = $match['oddHome'] ?? null;
            $oddDraw = $match['oddDraw'] ?? null;
            $oddAway = $match['oddAway'] ?? null;

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

            // Дата
            $matchDate = null;
            if (!empty($match['date'])) {
                $season = $match['season'] ?? null;
                $matchDate = $this->parseBetExplorerDate($match['date'], $season);
            }

            // Сезон
            $season = $match['season'] ?? null;

            // Статус (по наличию счёта)
            $status = (!is_null($match['homeScore']) && !is_null($match['awayScore'])) ? 'finished' : 'scheduled';

            // Уникальный ID
            $betexplorerId = $match['matchId'] ?? md5($match['date'] . $match['homeTeam'] . $match['awayTeam']);

            $matchGame = MatchGame::updateOrCreate(
                ['betexplorer_id' => $betexplorerId],
                [
                    'league_id' => $league->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'match_date' => $matchDate,
                    'home_score' => $match['homeScore'],
                    'away_score' => $match['awayScore'],
                    'url' => $match['fullUrl'] ?? ($match['matchId'] ? "https://www.betexplorer.com/match/{$match['matchId']}/" : ''),
                    'odd_home' => $oddHome,
                    'odd_draw' => $oddDraw,
                    'odd_away' => $oddAway,
                    'season' => $season,
                    'match_status' => $status,
                ]
            );


            // После сохранения $matchGame
            if (isset($match['ah']) && $match['ah']) {
                $ah = $match['ah'];
                if (isset($ah['balanced'])) {
                    AsianHandicap::updateOrCreate(
                        ['match_game_id' => $matchGame->id, 'type' => 'balanced'],
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
                        ['match_game_id' => $matchGame->id, 'type' => 'purchase'],
                        [
                            'home_handicap' => $ah['purchase']['homeHandicap'],
                            'away_handicap' => $ah['purchase']['awayHandicap'],
                            'home_odds' => $ah['purchase']['homeOdds'],
                            'away_odds' => $ah['purchase']['awayOdds'],
                        ]
                    );
                }
            }




            $saved++;
        }
        return $saved;
    }


    public function collectAh()
    {
        $this->output = "⏳ Запуск сбора азиатских фор (AH)...";

        // Выполняем команду синхронно
        Artisan::call('ah:collect', ['--limit' => 50]);

        $output = Artisan::output();

        if (str_contains($output, 'error') || str_contains($output, 'Error')) {
            $this->output = "❌ Ошибка при сборе AH:\n" . $output;
        } else {
            $this->output = "✅ Сбор AH завершён:\n" . $output;
        }
    }


    public function collectAhBatch()
    {
        // Подсчитываем количество матчей без AH
        $total = MatchGame::doesntHave('asianHandicaps')
            ->whereNotNull('url')
            ->count();

        if ($total == 0) {
            $this->output = "✅ Все матчи уже имеют азиатские форы.";
            return;
        }

        $limit = 20; // размер пакета (можно сделать настраиваемым через .env)
        $offset = 0;
        $processed = 0;

        $this->output = "⏳ Начинаю пакетный сбор AH (всего $total матчей)...\n";

        while ($offset < $total) {
            $end = min($offset + $limit, $total);
            $this->output .= "⏳ Обработка матчей с " . ($offset + 1) . " по $end ...\n";

            Artisan::call('ah:collect', [
                '--limit' => $limit,
                '--offset' => $offset,
            ]);

            $output = Artisan::output();
            $this->output .= $output . "\n";

            $processed += $limit;
            $offset += $limit;

            // Небольшая задержка, чтобы снизить нагрузку на сервер
            sleep(1);
        }

        $this->output .= "✅ Пакетный сбор AH завершён! Обработано $processed матчей.";
    }



    /**
     * Преобразует текстовую дату с BetExplorer в формат Y-m-d H:i:s
     *
     * @param string|null $dateStr
     * @param string|null $season  (год сезона, например "2026")
     * @return string|null
     */
    private function parseBetExplorerDate($dateStr, $season = null): ?string
    {
        $dateStr = trim($dateStr ?? '');
        if (empty($dateStr)) {
            return null;
        }

        // Определяем год (если сезон передан, используем его, иначе текущий)
        $year = $season ? (int) $season : date('Y');
        $now = new \DateTime();

        // --- Обработка "Tomorrow HH:MM" ---
        if (preg_match('/^Tomorrow\s+(\d{1,2}:\d{2})$/i', $dateStr, $matches)) {
            $time = $matches[1];
            $date = clone $now;
            $date->modify('+1 day');
            list($hour, $minute) = explode(':', $time);
            $date->setTime((int)$hour, (int)$minute);
            return $date->format('Y-m-d H:i:s');
        }

        // --- Обработка "Today HH:MM" ---
        if (preg_match('/^Today\s+(\d{1,2}:\d{2})$/i', $dateStr, $matches)) {
            $time = $matches[1];
            $date = clone $now;
            list($hour, $minute) = explode(':', $time);
            $date->setTime((int)$hour, (int)$minute);
            return $date->format('Y-m-d H:i:s');
        }

        // --- Обработка "Day after tomorrow HH:MM" (если есть) ---
        if (preg_match('/^Day after tomorrow\s+(\d{1,2}:\d{2})$/i', $dateStr, $matches)) {
            $time = $matches[1];
            $date = clone $now;
            $date->modify('+2 days');
            list($hour, $minute) = explode(':', $time);
            $date->setTime((int)$hour, (int)$minute);
            return $date->format('Y-m-d H:i:s');
        }

        // --- Обработка "Yesterday" (без времени) ---
        if (preg_match('/^Yesterday$/i', $dateStr)) {
            $date = clone $now;
            $date->modify('-1 day');
            return $date->format('Y-m-d 00:00:00');
        }

        // --- Обработка "Yesterday HH:MM" (если есть) ---
        if (preg_match('/^Yesterday\s+(\d{1,2}:\d{2})$/i', $dateStr, $matches)) {
            $time = $matches[1];
            $date = clone $now;
            $date->modify('-1 day');
            list($hour, $minute) = explode(':', $time);
            $date->setTime((int)$hour, (int)$minute);
            return $date->format('Y-m-d H:i:s');
        }

        // --- Формат "20.06. 20:00" (день.месяц. час:минута) ---
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.\s+(\d{1,2}:\d{2})$/', $dateStr, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $time = $matches[3];
            $date = \DateTime::createFromFormat('Y-m-d H:i', "{$year}-{$month}-{$day} {$time}");
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
            return null;
        }

        // --- Формат "20.06." (день.месяц.) – без времени ---
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.$/', $dateStr, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $date = \DateTime::createFromFormat('Y-m-d', "{$year}-{$month}-{$day}");
            if ($date) {
                return $date->format('Y-m-d 00:00:00');
            }
            return null;
        }

        // --- Формат "16.06." (день.месяц.) – аналогично предыдущему ---

        // --- Формат "d.m.Y H:i" (полная дата с годом) ---
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})\s+(\d{1,2}:\d{2})$/', $dateStr, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $yearFromStr = (int)$matches[3];
            $time = $matches[4];
            $date = \DateTime::createFromFormat('Y-m-d H:i', "{$yearFromStr}-{$month}-{$day} {$time}");
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
            return null;
        }

        // --- Если строка уже похожа на "2026-06-18 16:18" (редко) ---
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $dateStr)) {
            $date = \DateTime::createFromFormat('Y-m-d H:i', substr($dateStr, 0, 16));
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // --- Попытка стандартного парсинга (на всякий случай) ---
        try {
            $date = new \DateTime($dateStr);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Если ничего не вышло, возвращаем null
            return null;
        }
    }




}


