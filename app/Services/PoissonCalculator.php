<?php

namespace App\Services;

use App\Models\MatchGame;
use App\Models\MatchPrediction;
use App\Models\TeamSeasonStat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PoissonCalculator
{
    /**
     * Рассчитать вероятности для всех матчей, у которых есть статистика
     */
    public function calculateAll(): void
    {
        $matches = MatchGame::whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->get();

        $saved = 0;
        foreach ($matches as $match) {
            if ($this->calculateForMatch($match)) {
                $saved++;
            }
        }

        Log::info("PoissonCalculator: обработано матчей: $saved");
    }

    /**
     * Рассчитать вероятности для одного матча (критерий 6)
     */
    public function calculateForMatch(MatchGame $match): bool
    {
        // 1. Получаем статистику для хозяев и гостей
        $homeStats = $this->getTeamStats($match->home_team_id, $match->league_id, $match->season);
        $awayStats = $this->getTeamStats($match->away_team_id, $match->league_id, $match->season);

        if (!$homeStats || !$awayStats) {
            // Недостаточно данных
            return false;
        }

        // 2. Рассчитываем средние голы по лиге (для сезона)
        $leagueAvgHome = $this->getLeagueAverageGoals($match->league_id, $match->season, 'home');
        $leagueAvgAway = $this->getLeagueAverageGoals($match->league_id, $match->season, 'away');

        if ($leagueAvgHome == 0 || $leagueAvgAway == 0) {
            return false;
        }

        // 3. Рассчитываем силы атаки и обороны
        $homeAttack = $this->calculateAttackStrength($homeStats, 'home', $leagueAvgHome);
        $homeDefense = $this->calculateDefenseStrength($homeStats, 'home', $leagueAvgHome);
        $awayAttack = $this->calculateAttackStrength($awayStats, 'away', $leagueAvgAway);
        $awayDefense = $this->calculateDefenseStrength($awayStats, 'away', $leagueAvgAway);

        // 4. Ожидаемые голы
        $expectedHomeGoals = $homeAttack * $awayDefense * $leagueAvgHome;
        $expectedAwayGoals = $awayAttack * $homeDefense * $leagueAvgAway;

        // Ограничиваем, чтобы не было слишком больших чисел
        $expectedHomeGoals = min($expectedHomeGoals, 5);
        $expectedAwayGoals = min($expectedAwayGoals, 5);

        // 5. Распределение Пуассона (вероятности счёта до 4-4)
        $probabilities = $this->calculatePoissonProbabilities($expectedHomeGoals, $expectedAwayGoals);

        // 6. Агрегируем в вероятности исходов
        $probHome = $probabilities['home'];
        $probDraw = $probabilities['draw'];
        $probAway = $probabilities['away'];

        // 7. Сохраняем результат
        MatchPrediction::updateOrCreate(
            [
                'match_game_id' => $match->id,
                'criteria_id' => 6,
                'is_average' => false,
            ],
            [
                'prob_home' => $probHome,
                'prob_draw' => $probDraw,
                'prob_away' => $probAway,
                'eff_home' => $probHome * $match->odd_home,
                'eff_draw' => $probDraw * $match->odd_draw,
                'eff_away' => $probAway * $match->odd_away,
            ]
        );

        return true;
    }

    /**
     * Получить статистику команды для лиги и сезона
     */
    private function getTeamStats($teamId, $leagueId, $season)
    {
        if (!$season) {
            // Если сезон не указан, берём любую статистику для этой команды и лиги
            return TeamSeasonStat::where('team_id', $teamId)
                ->where('league_id', $leagueId)
                ->first();
        }

        return TeamSeasonStat::where('team_id', $teamId)
            ->where('league_id', $leagueId)
            ->where('season', $season)
            ->first();
    }

    /**
     * Среднее количество голов за матч в лиге (дома/в гостях)
     */
    private function getLeagueAverageGoals($leagueId, $season, $field)
    {
        // Используем статистику команд для вычисления среднего
        $stats = TeamSeasonStat::where('league_id', $leagueId)
            ->where('season', $season)
            ->get();

        if ($stats->isEmpty()) {
            // Если статистики нет, возвращаем дефолтное значение ~1.3
            return 1.3;
        }

        $totalGoals = 0;
        $totalMatches = 0;

        foreach ($stats as $stat) {
            if ($field === 'home') {
                $totalGoals += $stat->goals_scored_home;
                $totalMatches += $stat->matches_home;
            } else {
                $totalGoals += $stat->goals_scored_away;
                $totalMatches += $stat->matches_away;
            }
        }

        if ($totalMatches == 0) {
            return 1.3;
        }

        return $totalGoals / $totalMatches;
    }

    /**
     * Сила атаки: голы забитые / средние голы в лиге
     */
    private function calculateAttackStrength(TeamSeasonStat $stats, $field, $leagueAvg)
    {
        $goalsScored = $field === 'home' ? $stats->goals_scored_home : $stats->goals_scored_away;
        $matches = $field === 'home' ? $stats->matches_home : $stats->matches_away;

        if ($matches == 0) {
            return 1.0;
        }

        $avg = $goalsScored / $matches;
        return $avg / $leagueAvg;
    }

    /**
     * Сила обороны: голы пропущенные / средние голы в лиге
     */
    private function calculateDefenseStrength(TeamSeasonStat $stats, $field, $leagueAvg)
    {
        $goalsConceded = $field === 'home' ? $stats->goals_conceded_home : $stats->goals_conceded_away;
        $matches = $field === 'home' ? $stats->matches_home : $stats->matches_away;

        if ($matches == 0) {
            return 1.0;
        }

        $avg = $goalsConceded / $matches;
        return $avg / $leagueAvg;
    }

    /**
     * Рассчитать вероятности всех исходов по распределению Пуассона
     */
    private function calculatePoissonProbabilities($lambdaHome, $lambdaAway)
    {
        $maxGoals = 5; // максимум голов для расчёта (5-5 достаточно)

        // Массив вероятностей для каждого счёта
        $scoreProb = [];

        for ($i = 0; $i <= $maxGoals; $i++) {
            for ($j = 0; $j <= $maxGoals; $j++) {
                $scoreProb[$i][$j] = $this->poisson($i, $lambdaHome) * $this->poisson($j, $lambdaAway);
            }
        }

        // Агрегируем в вероятности исходов
        $homeWins = 0;
        $draws = 0;
        $awayWins = 0;

        for ($i = 0; $i <= $maxGoals; $i++) {
            for ($j = 0; $j <= $maxGoals; $j++) {
                $prob = $scoreProb[$i][$j] ?? 0;
                if ($i > $j) {
                    $homeWins += $prob;
                } elseif ($i == $j) {
                    $draws += $prob;
                } else {
                    $awayWins += $prob;
                }
            }
        }

        // Нормализуем (сумма должна быть 1, но из-за ограничения по голам может быть меньше)
        $total = $homeWins + $draws + $awayWins;
        if ($total == 0) {
            return ['home' => 0.33, 'draw' => 0.33, 'away' => 0.33];
        }

        return [
            'home' => $homeWins / $total,
            'draw' => $draws / $total,
            'away' => $awayWins / $total,
        ];
    }

    /**
     * Формула Пуассона: P(k; λ) = (λ^k * e^(-λ)) / k!
     */
    private function poisson($k, $lambda)
    {
        if ($lambda == 0) {
            return $k == 0 ? 1 : 0;
        }
        return pow($lambda, $k) * exp(-$lambda) / $this->factorial($k);
    }

    private function factorial($n)
    {
        if ($n == 0) return 1;
        $result = 1;
        for ($i = 1; $i <= $n; $i++) {
            $result *= $i;
        }
        return $result;
    }
}
