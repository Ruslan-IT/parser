<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Models\MatchGame;
use App\Models\Team;
use App\Models\TeamSeasonStat;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Доступные параметры команды:
 *
 * --force
 *      Удалить существующую статистику и пересчитать заново.
 *
 * --team-id=ID
 *      Рассчитать статистику только для указанной команды.
 *
 * --season=YEAR
 *      Рассчитать статистику только для указанного сезона.
 *
 * Примеры:
 *
 *                                         php artisan stats:calculate
 * С пересчётом (очистка старых данных):   php artisan stats:calculate --force
 * Только для конкретной команды:          php artisan stats:calculate --team-id=15
 * Только для сезона:                      php artisan stats:calculate --season=2025
 */


class CalculateTeamStats extends Command
{
    protected $signature = 'stats:calculate
                            {--force : Пересчитать даже если данные уже есть}
                            {--team-id= : Рассчитать только для конкретной команды}
                            {--season= : Рассчитать только для конкретного сезона}';

    protected $description = 'Рассчитать статистику команд (очки, голы, форма) для таблицы team_season_stats';


    /**
     * Точка входа команды.
     *
     * Алгоритм работы:
     * 1. Получаем завершённые матчи.
     * 2. Фильтруем по параметрам команды.
     * 3. Группируем матчи по команде/лиге/сезону.
     * 4. Рассчитываем статистику.
     * 5. Сохраняем результат в team_season_stats.
     */

    public function handle()
    {
        $this->info('🚀 Начинаем расчёт статистики команд...');

        // Удаляем старые данные, если указан флаг --force
        if ($this->option('force')) {
            $this->warn('Удаляем старые данные...');
            TeamSeasonStat::truncate();
        }

        // Формируем запрос на получение всех завершённых матчей
        $query = MatchGame::whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->with(['homeTeam', 'awayTeam', 'league']);

        if ($this->option('team-id')) {
            $query->where(function ($q) {
                $q->where('home_team_id', $this->option('team-id'))
                    ->orWhere('away_team_id', $this->option('team-id'));
            });
        }

        if ($this->option('season')) {
            $query->where('season', $this->option('season'));
        }

        // Для производительности используем LazyCollection
        $matches = $query->orderBy('match_date')->get();

        if ($matches->isEmpty()) {
            $this->error('❌ Нет завершённых матчей для расчёта.');
            return 1;
        }

        $this->info("📊 Найдено матчей: " . $matches->count());

        // Группируем матчи по командам, лигам и сезонам
        $groups = $this->groupMatches($matches);

        $bar = $this->output->createProgressBar(count($groups));
        $bar->start();

        $saved = 0;

        foreach ($groups as $key => $group) {
            // $key = "teamId|leagueId|season"
            list($teamId, $leagueId, $season) = explode('|', $key);
            $team = Team::find($teamId);
            $league = $leagueId ? League::find($leagueId) : null;

            if (!$team) {
                $bar->advance();
                continue;
            }

            $stats = $this->calculateStatsForTeam($group, $teamId, $leagueId, $season);

            // Сохраняем или обновляем
            TeamSeasonStat::updateOrCreate(
                [
                    'team_id' => $teamId,
                    'league_id' => $leagueId,
                    'season' => $season,
                ],
                $stats
            );

            $saved++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Готово! Сохранено записей: $saved");
        return 0;
    }

    /**
     * Группирует матчи по ключу:
     *
     * team_id|league_id|season
     *
     * Один матч попадает в две группы:
     * - для хозяев
     * - для гостей
     *
     * Пример ключа:
     *
     * 15|3|2025
     *
     * где:
     * 15 - команда
     * 3  - лига
     * 2025 - сезон
     */
    private function groupMatches($matches)
    {
        $groups = [];

        foreach ($matches as $match) {
            $leagueId = $match->league_id ?? 0; // 0 означает "без лиги"
            $season = $match->season ?? 'unknown';

            // Матч для хозяев
            $keyHome = $match->home_team_id . '|' . $leagueId . '|' . $season;
            $groups[$keyHome][] = $match;

            // Матч для гостей
            $keyAway = $match->away_team_id . '|' . $leagueId . '|' . $season;
            $groups[$keyAway][] = $match;
        }

        return $groups;
    }

    /**
     * Рассчитывает статистику команды
     * в рамках конкретной лиги и сезона.
     *
     * Рассчитываются:
     * - матчи
     * - очки
     * - голы
     * - разница голов
     * - домашняя статистика
     * - гостевая статистика
     * - последние 5 матчей
     */
    private function calculateStatsForTeam($matches, $teamId, $leagueId, $season)
    {
        // Сортируем матчи по дате (уже должно быть отсортировано, но на всякий случай)
        $matches = collect($matches)->sortBy('match_date');

        $stats = [
            'team_id' => $teamId,
            'league_id' => $leagueId,
            'season' => $season,
            'matches_total' => 0,
            'matches_home' => 0,
            'matches_away' => 0,
            'points_total' => 0,
            'points_home' => 0,
            'points_away' => 0,
            'goals_scored_total' => 0,
            'goals_scored_home' => 0,
            'goals_scored_away' => 0,
            'goals_conceded_total' => 0,
            'goals_conceded_home' => 0,
            'goals_conceded_away' => 0,
            'goals_diff_total' => 0,
            'goals_diff_home' => 0,
            'goals_diff_away' => 0,
        ];

        // Для скользящего окна последних 5 матчей
        $last5 = [];
        $last5Home = [];
        $last5Away = [];

        foreach ($matches as $match) {
            $isHome = ($match->home_team_id == $teamId);
            $goalsFor = $isHome ? $match->home_score : $match->away_score;
            $goalsAgainst = $isHome ? $match->away_score : $match->home_score;
            $points = $this->getPoints($goalsFor, $goalsAgainst);

            // Общая статистика
            $stats['matches_total']++;
            $stats['goals_scored_total'] += $goalsFor;
            $stats['goals_conceded_total'] += $goalsAgainst;
            $stats['points_total'] += $points;

            if ($isHome) {
                $stats['matches_home']++;
                $stats['goals_scored_home'] += $goalsFor;
                $stats['goals_conceded_home'] += $goalsAgainst;
                $stats['points_home'] += $points;
                // Для скользящего окна дома
                $last5Home[] = $points;
                if (count($last5Home) > 5) array_shift($last5Home);
            } else {
                $stats['matches_away']++;
                $stats['goals_scored_away'] += $goalsFor;
                $stats['goals_conceded_away'] += $goalsAgainst;
                $stats['points_away'] += $points;
                $last5Away[] = $points;
                if (count($last5Away) > 5) array_shift($last5Away);
            }

            // Общее скользящее окно (все матчи)
            $last5[] = $points;
            if (count($last5) > 5) array_shift($last5);

            // Обновляем разницу мячей
            $stats['goals_diff_total'] = $stats['goals_scored_total'] - $stats['goals_conceded_total'];
            $stats['goals_diff_home'] = $stats['goals_scored_home'] - $stats['goals_conceded_home'];
            $stats['goals_diff_away'] = $stats['goals_scored_away'] - $stats['goals_conceded_away'];
        }

        // Статистика последних 5 матчей
        $stats['points_last5'] = array_sum($last5);
        $stats['points_last5_home'] = array_sum($last5Home);
        $stats['points_last5_away'] = array_sum($last5Away);

        // Голы за последние 5 матчей (для простоты считаем только общие, но можно и отдельно)
        // Для простоты пока считаем только общее количество голов в последних 5 матчах
        // Можно было бы тоже разделить, но пока упростим
        $last5GoalsFor = 0;
        $last5GoalsAgainst = 0;
        $last5Matches = $matches->slice(-5);
        foreach ($last5Matches as $match) {
            $isHome = ($match->home_team_id == $teamId);
            $last5GoalsFor += $isHome ? $match->home_score : $match->away_score;
            $last5GoalsAgainst += $isHome ? $match->away_score : $match->home_score;
        }
        $stats['goals_scored_last5'] = $last5GoalsFor;
        $stats['goals_conceded_last5'] = $last5GoalsAgainst;

        return $stats;
    }



    /**
     * Возвращает количество очков за матч.
     *
     * Победа = 3 очка
     * Ничья  = 1 очко
     * Поражение = 0 очков
     */

    private function getPoints($goalsFor, $goalsAgainst)
    {
        if ($goalsFor > $goalsAgainst) return 3;
        if ($goalsFor == $goalsAgainst) return 1;
        return 0;
    }

}
