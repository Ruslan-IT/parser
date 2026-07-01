<?php

namespace App\Services;

use App\Models\MatchGame;
use App\Models\CriteriaValue;
use App\Models\MatchPrediction;
use App\Models\TeamSeasonStat;
use App\Models\CriteriaCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ProbabilityCalculator
{
    /**
     * Рассчитать вероятности и эффективности для всех матчей, у которых есть критерии
     */
    public function calculateAll()
    {
        $matches = MatchGame::whereHas('criteriaValue')->get();
        $results = [];

        foreach ($matches as $match) {
            $result = $this->calculateForMatch($match);
            if ($result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Рассчитать вероятности для одного матча
     */
    public function calculateForMatch(MatchGame $match)
    {
        $criteria = $match->criteriaValue;
        if (!$criteria) {
            return null;
        }

        // Определяем категорию для хозяев и гостей
        $homeCategory = $this->getCategoryForTeam($match->home_team_id, $match->league_id, $match->season);
        $awayCategory = $this->getCategoryForTeam($match->away_team_id, $match->league_id, $match->season);

        // Для простоты используем среднюю категорию (или минимальную)
        $category = $this->getCombinedCategory($homeCategory, $awayCategory);

        $predictions = [];

        // По каждому критерию (1–6)
        for ($i = 1; $i <= 6; $i++) {
            $criteriaValue = $criteria->{'criteria_' . $i};
            if ($criteriaValue === null) continue;

            // Получаем исторические матчи с похожим значением критерия
            $historicalMatches = $this->getHistoricalMatches($match, $i, $criteriaValue, $category);

            if ($historicalMatches->isEmpty()) {
                continue;
            }

            // Считаем частоты исходов
            $frequencies = $this->calculateFrequencies($historicalMatches);

            // Получаем коэффициенты
            $oddHome = $match->odd_home;
            $oddDraw = $match->odd_draw;
            $oddAway = $match->odd_away;

            // Вычисляем эффективности
            $effHome = $oddHome * $frequencies['home'];
            $effDraw = $oddDraw * $frequencies['draw'];
            $effAway = $oddAway * $frequencies['away'];

            $predictions[] = [
                'criteria_id' => $i,
                'category_id' => $category?->id,
                'prob_home' => $frequencies['home'],
                'prob_draw' => $frequencies['draw'],
                'prob_away' => $frequencies['away'],
                'eff_home' => $effHome,
                'eff_draw' => $effDraw,
                'eff_away' => $effAway,
                // Для AH пока не считаем (можно позже)
            ];
        }

        if (empty($predictions)) {
            return null;
        }

        // Сохраняем все предсказания для этого матча
        foreach ($predictions as $pred) {
            MatchPrediction::updateOrCreate(
                [
                    'match_game_id' => $match->id,
                    'criteria_id' => $pred['criteria_id'],
                    'is_average' => false,
                ],
                $pred
            );
        }

        // Вычисляем средние значения по всем критериям
        $this->saveAveragePredictions($match, $predictions);

        return $predictions;
    }

    private function getCategoryForTeam($teamId, $leagueId, $season)
    {
        $stats = TeamSeasonStat::where('team_id', $teamId)
            ->where('league_id', $leagueId)
            ->where('season', $season)
            ->first();

        if (!$stats) {
            return null;
        }

        $matchesCount = $stats->matches_total;

        return CriteriaCategory::where('min_matches', '<=', $matchesCount)
            ->where(function ($q) use ($matchesCount) {
                $q->where('max_matches', '>=', $matchesCount)
                    ->orWhereNull('max_matches');
            })
            ->first();
    }

    private function getCombinedCategory($cat1, $cat2)
    {
        if (!$cat1 && !$cat2) return null;
        if (!$cat1) return $cat2;
        if (!$cat2) return $cat1;
        // Берём категорию с бóльшим количеством матчей (более строгую)
        return $cat1->min_matches >= $cat2->min_matches ? $cat1 : $cat2;
    }

    private function getHistoricalMatches(MatchGame $match, $criteriaId, $value, $category)
    {
        // Находим все завершённые матчи (кроме текущего) в той же лиге и сезоне,
        // у которых значение критерия близко к текущему (в пределах ±1)
        $range = 1;

        return MatchGame::where('id', '!=', $match->id)
            ->where('league_id', $match->league_id)
            ->where('season', $match->season)
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->whereHas('criteriaValue', function ($q) use ($criteriaId, $value, $range) {
                $q->whereBetween('criteria_' . $criteriaId, [$value - $range, $value + $range]);
            })
            // Дополнительно можно ограничить по категории (если нужно)
            // ->whereHas('...')
            ->limit(500) // ограничим, чтобы не перегружать
            ->get();
    }

    private function calculateFrequencies(Collection $matches)
    {
        $total = $matches->count();
        if ($total == 0) {
            return ['home' => 0, 'draw' => 0, 'away' => 0];
        }

        $homeWins = 0;
        $draws = 0;
        $awayWins = 0;

        foreach ($matches as $m) {
            if ($m->home_score > $m->away_score) $homeWins++;
            elseif ($m->home_score == $m->away_score) $draws++;
            else $awayWins++;
        }

        return [
            'home' => $homeWins / $total,
            'draw' => $draws / $total,
            'away' => $awayWins / $total,
        ];
    }

    private function saveAveragePredictions(MatchGame $match, array $predictions)
    {
        $count = count($predictions);
        if ($count == 0) return;

        $avgProbHome = array_sum(array_column($predictions, 'prob_home')) / $count;
        $avgProbDraw = array_sum(array_column($predictions, 'prob_draw')) / $count;
        $avgProbAway = array_sum(array_column($predictions, 'prob_away')) / $count;
        $avgEffHome = array_sum(array_column($predictions, 'eff_home')) / $count;
        $avgEffDraw = array_sum(array_column($predictions, 'eff_draw')) / $count;
        $avgEffAway = array_sum(array_column($predictions, 'eff_away')) / $count;

        MatchPrediction::updateOrCreate(
            [
                'match_game_id' => $match->id,
                'criteria_id' => null,
                'is_average' => true,
            ],
            [
                'category_id' => $predictions[0]['category_id'] ?? null,
                'prob_home' => $avgProbHome,
                'prob_draw' => $avgProbDraw,
                'prob_away' => $avgProbAway,
                'eff_home' => $avgEffHome,
                'eff_draw' => $avgEffDraw,
                'eff_away' => $avgEffAway,
            ]
        );
    }





}



