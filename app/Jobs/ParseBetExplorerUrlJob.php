<?php

namespace App\Jobs;

use App\Models\AsianHandicap;
use App\Models\League;
use App\Models\MatchGame;
use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ParseBetExplorerUrlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;      // 10 минут на выполнение одной ссылки
    public $tries = 3;          // количество попыток при сбое

    protected string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Основной метод обработки задания.
     */
    public function handle(): void
    {
        $nodePath = env('NODE_PATH', 'node');
        $scriptPath = base_path('parser.js');

        $result = Process::path(base_path())->run([$nodePath, $scriptPath, $this->url]);

        if ($result->failed()) {
            Log::error("Ошибка парсинга {$this->url}: " . $result->errorOutput());
            $this->fail(new \Exception("Ошибка парсинга: " . $result->errorOutput()));
            return;
        }

        $output = $result->output();
        $matches = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Некорректный JSON от {$this->url}: " . json_last_error_msg());
            $this->fail(new \Exception("JSON error: " . json_last_error_msg()));
            return;
        }

        if (!is_array($matches) || empty($matches)) {
            Log::warning("Нет данных для {$this->url}");
            return;
        }

        // Сохраняем полученные матчи
        $this->saveMatchesToDatabase($matches);
    }

    /**
     * Сохранение массива матчей в БД.
     * Полностью скопировано из Parser::saveMatchesToDatabase()
     */
    private function saveMatchesToDatabase(array $matches): int
    {
        $saved = 0;
        foreach ($matches as $match) {
            if (empty($match['homeTeam']) || empty($match['awayTeam'])) {
                continue;
            }

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
            if (empty($season) && $matchDate) {
                try {
                    $season = \Carbon\Carbon::parse($matchDate)->year;
                } catch (\Exception $e) {}
            }

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

            // Сохранение азиатских фор (AH)
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

    /**
     * Преобразует текстовую дату с BetExplorer в формат Y-m-d H:i:s.
     * Полностью скопировано из Parser::parseBetExplorerDate()
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

        // --- Обработка "Day after tomorrow HH:MM" ---
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

        // --- Обработка "Yesterday HH:MM" ---
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

        // --- Если строка уже похожа на "2026-06-18 16:18" ---
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $dateStr)) {
            $date = \DateTime::createFromFormat('Y-m-d H:i', substr($dateStr, 0, 16));
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // --- Попытка стандартного парсинга ---
        try {
            $date = new \DateTime($dateStr);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
