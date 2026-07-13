<?php

namespace App\Filament\Pages;

use App\Console\Commands\CollectAsianHandicaps;
use App\Jobs\CollectAsianHandicapsJob;
use App\Models\AsianHandicap;
use App\Models\CriteriaValue;
use App\Models\League;
use App\Models\MatchPrediction;
use App\Models\SavedUrlSet;
use App\Models\TeamSeasonStat;
use App\Services\CriteriaCalculator;
use App\Services\PoissonCalculator;
use App\Services\ProbabilityCalculator;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

use App\Models\Team;
use App\Models\MatchGame;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

    public $selectedUrlSet = '';

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

        $limit = 10; // размер пакета (можно сделать настраиваемым через .env)
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



    public function calculateStats()
    {
        $this->output = "⏳ Запуск расчёта статистики команд...\n";
        Artisan::call('stats:calculate', ['--force' => true]);
        $this->output .= Artisan::output();
        $this->output .= "\n✅ Статистика обновлена!";
    }



    public function calculateCriteria()
    {
        $this->output = "⏳ Расчёт критериев 1–5...\n";

        $matches = MatchGame::whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->get();

        $calculator = new CriteriaCalculator();
        $saved = 0;

        foreach ($matches as $match) {
            $result = $calculator->calculateForMatch($match);
            if ($result) {
                CriteriaValue::updateOrCreate(
                    ['match_game_id' => $match->id],
                    $result
                );
                $saved++;
            }
        }

        $this->output .= "✅ Сохранено критериев для $saved матчей.";
    }


    public function calculateProbabilities()
    {
        $this->output = "⏳ Расчёт вероятностей и эффективностей...\n";
        $calculator = new ProbabilityCalculator();
        $results = $calculator->calculateAll();
        $this->output .= "✅ Сохранено прогнозов для " . count($results) . " матчей.";
    }


    public function calculatePoisson()
    {
        $this->output = "⏳ Расчёт критерия Пуассона (критерий 6)...\n";
        $calculator = new PoissonCalculator();
        $calculator->calculateAll();
        $this->output .= "✅ Критерий Пуассона рассчитан.";
    }




    public function recalculateAverages()
    {
        $this->output = "⏳ Пересчёт средних вероятностей по всем критериям...\n";

        $matches = MatchGame::whereHas('matchPredictions', function ($q) {
            $q->whereNotNull('prob_home');
        })->get();

        $updated = 0;
        foreach ($matches as $match) {
            $predictions = $match->matchPredictions()->where('is_average', false)->get();
            if ($predictions->isEmpty()) continue;

            $avgProbHome = $predictions->avg('prob_home');
            $avgProbDraw = $predictions->avg('prob_draw');
            $avgProbAway = $predictions->avg('prob_away');
            $avgEffHome = $predictions->avg('eff_home');
            $avgEffDraw = $predictions->avg('eff_draw');
            $avgEffAway = $predictions->avg('eff_away');

            MatchPrediction::updateOrCreate(
                [
                    'match_game_id' => $match->id,
                    'criteria_id' => null,
                    'is_average' => true,
                ],
                [
                    'prob_home' => $avgProbHome,
                    'prob_draw' => $avgProbDraw,
                    'prob_away' => $avgProbAway,
                    'eff_home' => $avgEffHome,
                    'eff_draw' => $avgEffDraw,
                    'eff_away' => $avgEffAway,
                ]
            );
            $updated++;
        }

        $this->output .= "✅ Обновлены средние для $updated матчей.";
    }


    public function exportCsv()
    {
        $this->output = "⏳ Генерация CSV-файла...\n";

        $matches = MatchGame::with(['homeTeam', 'awayTeam', 'matchPredictions' => function ($q) {
            $q->where('is_average', true);
        }])->get();

        if ($matches->isEmpty()) {
            $this->output = "❌ Нет матчей с прогнозами.";
            return;
        }

        $filename = 'predictions_' . date('Y-m-d_H-i-s') . '.csv';
        $path = storage_path('app/public/' . $filename);

        // Открываем файл с BOM для UTF-8
        $handle = fopen($path, 'w');
        fprintf($handle, "\xEF\xBB\xBF"); // BOM для UTF-8

        // Заголовки
        fputcsv($handle, [
            'Дата', 'Лига', 'Хозяева', 'Гости',
            'Кэф 1', 'Кэф X', 'Кэф 2',
            'Вероятность P1', 'Вероятность PX', 'Вероятность P2',
            'Эффективность E1', 'Эффективность EX', 'Эффективность E2',
            'Прогноз'
        ]);

        foreach ($matches as $match) {
            $avg = $match->matchPredictions->first();
            if (!$avg) continue;
            $probHome = $avg->prob_home ?? 0;
            $probDraw = $avg->prob_draw ?? 0;
            $probAway = $avg->prob_away ?? 0;

            $maxProb = max($probHome, $probDraw, $probAway);
            $prediction = '';
            if ($maxProb == $probHome) $prediction = 'Победа хозяев';
            elseif ($maxProb == $probDraw) $prediction = 'Ничья';
            else $prediction = 'Победа гостей';

            $date = $match->match_date ? \Carbon\Carbon::parse($match->match_date)->format('d.m.Y') : '';

            fputcsv($handle, [
                $date,
                $match->league->name ?? '',
                $match->homeTeam->name ?? '',
                $match->awayTeam->name ?? '',
                $match->odd_home ?? '',
                $match->odd_draw ?? '',
                $match->odd_away ?? '',
                round($probHome * 100, 1) . '%',
                round($probDraw * 100, 1) . '%',
                round($probAway * 100, 1) . '%',
                round($avg->eff_home ?? 0, 3),
                round($avg->eff_draw ?? 0, 3),
                round($avg->eff_away ?? 0, 3),
                $prediction,
            ]);
        }

        fclose($handle);

        $url = asset('storage/' . $filename);
        $this->output = "✅ CSV-файл сохранён: <a href='$url' target='_blank'>Скачать CSV</a> (откроется в новой вкладке)";
        $this->dispatch('download-csv', ['url' => $url]);
    }

    public function exportExcel()
    {
        $this->output = "⏳ Генерация Excel-файла с форами...\n";

        $matches = MatchGame::with([
            'league',
            'homeTeam',
            'awayTeam',
            'asianHandicaps',
            'averagePrediction',
        ])->get();

        if ($matches->isEmpty()) {
            $this->output = "❌ Нет матчей для экспорта.";
            return;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // --- Заголовки ---
        $headers = [
            'Дата',
            'Лига',
            'Хозяева',
            'Гости',

            // Равновесная фора
            'Равн_индекс_дом',
            'Равн_индекс_гости',
            'Равн_коэф_дом',
            'Равн_коэф_гости',
            'Равн_вер_дом',
            'Равн_вер_гости',
            'Равн_эф_дом',
            'Равн_эф_гости',

            // Покупная фора
            'Покуп_индекс_дом',
            'Покуп_индекс_гости',
            'Покуп_коэф_дом',
            'Покуп_коэф_гости',
            'Покуп_вер_дом',
            'Покуп_вер_гости',
            'Покуп_эф_дом',
            'Покуп_эф_гости',
        ];

        $colIndex = 0;
        foreach ($headers as $header) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
            $colIndex++;
        }

        // --- Стилизация заголовков (ЗЕЛЁНАЯ ШАПКА) ---
        $lastColLetter = Coordinate::stringFromColumnIndex($colIndex);
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']], // тёмно-зелёный
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:' . $lastColLetter . '1')->applyFromArray($headerStyle);

        // --- Заполнение данными и стилизация строк ---
        $row = 2;
        foreach ($matches as $match) {
            $balanced = $match->asianHandicaps->where('type', 'balanced')->first();
            $purchase = $match->asianHandicaps->where('type', 'purchase')->first();
            $avg = $match->averagePrediction;

            $data = [
                $match->match_date?->format('d.m.Y') ?? '',
                $match->league->name ?? '',
                $match->homeTeam->name ?? '',
                $match->awayTeam->name ?? '',

                // Равновесная
                $balanced->home_handicap ?? '',
                $balanced->away_handicap ?? '',
                $balanced->home_odds ?? '',
                $balanced->away_odds ?? '',
                $avg->handicap_home_prob ?? '',
                $avg->handicap_away_prob ?? '',
                $avg->handicap_home_eff ?? '',
                $avg->handicap_away_eff ?? '',

                // Покупная
                $purchase->home_handicap ?? '',
                $purchase->away_handicap ?? '',
                $purchase->home_odds ?? '',
                $purchase->away_odds ?? '',
                $avg->handicap_home_prob ?? '',
                $avg->handicap_away_prob ?? '',
                $avg->handicap_home_eff ?? '',
                $avg->handicap_away_eff ?? '',
            ];

            $colIndexData = 0;
            foreach ($data as $value) {
                $colLetter = Coordinate::stringFromColumnIndex($colIndexData + 1);
                // Форматируем проценты и числа
                if (is_numeric($value) && str_contains($headers[$colIndexData] ?? '', 'вер')) {
                    $sheet->setCellValue($colLetter . $row, $value !== '' ? round($value * 100, 1) . '%' : '');
                } elseif (is_numeric($value)) {
                    $sheet->setCellValue($colLetter . $row, $value !== '' ? round($value, 3) : '');
                } else {
                    $sheet->setCellValue($colLetter . $row, $value);
                }
                $colIndexData++;
            }

            // Стилизация строк (чередование цветов)
            $rowRange = 'A' . $row . ':' . $lastColLetter . $row;
            if ($row % 2 == 0) {
                // Чётные строки – белый фон
                $sheet->getStyle($rowRange)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D0D0D0'],
                        ],
                    ],
                ]);
            } else {
                // Нечётные строки – светло-серый фон
                $sheet->getStyle($rowRange)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D0D0D0'],
                        ],
                    ],
                ]);
            }

            $row++;
        }

        // --- Автоширина колонок ---
        foreach (range(1, $colIndex) as $i) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Сохранение и скачивание
        $filename = 'predictions_handicaps_' . date('Y-m-d_H-i-s') . '.xlsx';
        $path = storage_path('app/public/' . $filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        $url = asset('storage/' . $filename);
        $this->output = "✅ Excel-файл с форами сохранён: <a href='$url' target='_blank'>Скачать Excel</a>";
        $this->dispatch('download-excel', ['url' => $url]);
    }


    public function exportExcel1()
    {
        $this->output = "⏳ Генерация Excel-файла...\n";

        $matches = MatchGame::with(['homeTeam', 'awayTeam', 'matchPredictions' => function ($q) {
            $q->where('is_average', true);
        }])->get();

        if ($matches->isEmpty()) {
            $this->output = "❌ Нет матчей с прогнозами.";
            return;
        }

        // Создаём новый документ
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки (стилизованные)
        $headers = [
            'Дата', 'Лига', 'Хозяева', 'Гости',
            'Кэф 1', 'Кэф X', 'Кэф 2',
            'Вероятность P1', 'Вероятность PX', 'Вероятность P2',
            'Эффективность E1', 'Эффективность EX', 'Эффективность E2',
            'Прогноз'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Заполняем данные
        $row = 2;
        foreach ($matches as $match) {
            $avg = $match->matchPredictions->first();
            if (!$avg) continue;

            $probHome = $avg->prob_home ?? 0;
            $probDraw = $avg->prob_draw ?? 0;
            $probAway = $avg->prob_away ?? 0;

            $maxProb = max($probHome, $probDraw, $probAway);
            $prediction = '';
            if ($maxProb == $probHome) $prediction = 'Победа хозяев';
            elseif ($maxProb == $probDraw) $prediction = 'Ничья';
            else $prediction = 'Победа гостей';

            $date = $match->match_date ? \Carbon\Carbon::parse($match->match_date)->format('d.m.Y') : '';

            $sheet->setCellValue('A' . $row, $date);
            $sheet->setCellValue('B' . $row, $match->league->name ?? '');
            $sheet->setCellValue('C' . $row, $match->homeTeam->name ?? '');
            $sheet->setCellValue('D' . $row, $match->awayTeam->name ?? '');
            $sheet->setCellValue('E' . $row, $match->odd_home ?? '');
            $sheet->setCellValue('F' . $row, $match->odd_draw ?? '');
            $sheet->setCellValue('G' . $row, $match->odd_away ?? '');
            $sheet->setCellValue('H' . $row, round($probHome * 100, 1) . '%');
            $sheet->setCellValue('I' . $row, round($probDraw * 100, 1) . '%');
            $sheet->setCellValue('J' . $row, round($probAway * 100, 1) . '%');
            $sheet->setCellValue('K' . $row, round($avg->eff_home ?? 0, 3));
            $sheet->setCellValue('L' . $row, round($avg->eff_draw ?? 0, 3));
            $sheet->setCellValue('M' . $row, round($avg->eff_away ?? 0, 3));
            $sheet->setCellValue('N' . $row, $prediction);

            $row++;
        }

        // --- Применяем стили ---

        // Стиль для заголовков (жирный, центрированный, фон)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        // Автоширина для всех колонок
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Выравнивание данных
        $sheet->getStyle('A2:N' . ($row-1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Чередование цветов строк
        for ($i = 2; $i < $row; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':N' . $i)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5');
            }
        }

        // Добавляем границы для всех ячеек с данными
        $sheet->getStyle('A1:N' . ($row-1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Сохраняем файл
        $writer = new Xlsx($spreadsheet);
        $filename = 'predictions_' . date('Y-m-d_H-i-s') . '.xlsx';
        $path = storage_path('app/public/' . $filename);
        $writer->save($path);

        $url = asset('storage/' . $filename);
        $this->output = "✅ Excel-файл сохранён: <a href='$url' target='_blank'>Скачать Excel</a> (откроется в новой вкладке)";

        // Отправляем событие для автоматического скачивания
        $this->dispatch('download-csv', ['url' => $url]);
    }


    public function clearStats()
    {
        $this->output = "⏳ Очистка таблицы team_season_stats...\n";
        TeamSeasonStat::truncate();
        $this->output .= "✅ Таблица team_season_stats очищена.";
    }

    public function clearCriteria()
    {
        $this->output = "⏳ Очистка таблицы criteria_values...\n";
        CriteriaValue::truncate();
        $this->output .= "✅ Таблица criteria_values очищена.";
    }

    public function clearPredictions()
    {
        $this->output = "⏳ Очистка таблицы match_predictions...\n";
        MatchPrediction::truncate();
        $this->output .= "✅ Таблица match_predictions очищена.";
    }

    public function clearHandicaps()
    {
        $this->output = "⏳ Очистка таблицы asian_handicaps...\n";
        AsianHandicap::truncate();
        $this->output .= "✅ Таблица asian_handicaps очищена.";
    }

    public function clearAll()
    {
        $this->output = "⏳ Очистка всех расчётных таблиц...\n";
        TeamSeasonStat::truncate();
        CriteriaValue::truncate();
        MatchPrediction::truncate();
        AsianHandicap::truncate();
        MatchGame::truncate();
        $this->output .= "✅ Все расчётные таблицы очищены.";
    }




    public function calculateFuturePredictions()
    {
        $this->output = "⏳ Расчёт прогнозов для будущих матчей...\n";

        $futureMatches = MatchGame::where('match_status', 'scheduled')->get();

        if ($futureMatches->isEmpty()) {
            $this->output .= "❌ Нет будущих матчей. Сначала спарсите расписание (/fixtures/).";
            return;
        }

        // 1. Критерии 1–5
        $criteriaCalculator = new CriteriaCalculator();
        $criteriaSaved = 0;
        foreach ($futureMatches as $match) {
            $result = $criteriaCalculator->calculateForMatch($match);
            if ($result) {
                CriteriaValue::updateOrCreate(
                    ['match_game_id' => $match->id],
                    $result
                );
                $criteriaSaved++;
            }
        }
        $this->output .= "✅ Сохранено критериев для $criteriaSaved будущих матчей.\n";

        // 2. Вероятности (критерии 1–6)
        $probCalculator = new ProbabilityCalculator();
        $probSaved = 0;
        foreach ($futureMatches as $match) {
            $result = $probCalculator->calculateForMatch($match);
            if ($result) {
                $probSaved++;
            }
        }
        $this->output .= "✅ Сохранено вероятностей для $probSaved будущих матчей.\n";

        // 3. Пуассон (критерий 6)
        $poissonCalculator = new PoissonCalculator();
        $poissonSaved = 0;
        foreach ($futureMatches as $match) {
            if ($poissonCalculator->calculateForMatch($match)) {
                $poissonSaved++;
            }
        }
        $this->output .= "✅ Сохранено Пуассона для $poissonSaved будущих матчей.\n";

        // 4. Пересчёт средних
        $this->recalculateAveragesForMatches($futureMatches);

        $this->output .= "🎯 Прогнозы для будущих матчей рассчитаны! Перейдите в раздел «Будущие матчи» для просмотра.";
    }

    private function recalculateAveragesForMatches($matches)
    {
        $updated = 0;
        foreach ($matches as $match) {
            $predictions = $match->matchPredictions()->where('is_average', false)->get();
            if ($predictions->isEmpty()) continue;

            $avgProbHome = $predictions->avg('prob_home');
            $avgProbDraw = $predictions->avg('prob_draw');
            $avgProbAway = $predictions->avg('prob_away');
            $avgEffHome = $predictions->avg('eff_home');
            $avgEffDraw = $predictions->avg('eff_draw');
            $avgEffAway = $predictions->avg('eff_away');

            MatchPrediction::updateOrCreate(
                [
                    'match_game_id' => $match->id,
                    'criteria_id' => null,
                    'is_average' => true,
                ],
                [
                    'prob_home' => $avgProbHome,
                    'prob_draw' => $avgProbDraw,
                    'prob_away' => $avgProbAway,
                    'eff_home' => $avgEffHome,
                    'eff_draw' => $avgEffDraw,
                    'eff_away' => $avgEffAway,
                ]
            );
            $updated++;
        }
        $this->output .= "✅ Обновлены средние для $updated будущих матчей.\n";
    }



    public function loadUrlSet()
    {
        if ($this->selectedUrlSet) {
            $set = SavedUrlSet::find($this->selectedUrlSet);
            if ($set) {
                $this->urls = $set->urls;
            }
        }
    }




}


