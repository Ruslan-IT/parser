<?php

namespace App\Services;

use App\Models\MatchGame;
use App\Models\TeamSeasonStat;
use App\Models\AsianHandicap;

class CriteriaCalculator
{
    public function calculateForMatch(MatchGame $match)
    {
        $homeStats = TeamSeasonStat::where('team_id', $match->home_team_id)
            ->where('league_id', $match->league_id)
            ->where('season', $match->season)
            ->first();

        $awayStats = TeamSeasonStat::where('team_id', $match->away_team_id)
            ->where('league_id', $match->league_id)
            ->where('season', $match->season)
            ->first();

        if (!$homeStats || !$awayStats) {
            return null;
        }

        // Критерии 1–4
        $criteria1 = $homeStats->points_total - $awayStats->points_total;
        $criteria2 = $homeStats->points_home - $awayStats->points_away;
        $criteria3 = $homeStats->points_last5 - $awayStats->points_last5;
        $criteria4 = $homeStats->points_last5_home - $awayStats->points_last5_away;

        // Критерий 5: проход форы
        $criteria5 = $this->calculateHandicapPassRates($match);

        return [
            'criteria_1' => $criteria1,
            'criteria_2' => $criteria2,
            'criteria_3' => $criteria3,
            'criteria_4' => $criteria4,
            // criteria_5 можно сохранить для совместимости, но лучше заполнить только новые поля:
            'handicap_balanced_home' => $criteria5['balanced']['home'] ?? null,
            'handicap_balanced_away' => $criteria5['balanced']['away'] ?? null,
            'handicap_purchase_home' => $criteria5['purchase']['home'] ?? null,
            'handicap_purchase_away' => $criteria5['purchase']['away'] ?? null,
        ];
    }

    private function calculateHandicapPassRates(MatchGame $match)
    {
        $result = [];

        foreach (['balanced', 'purchase'] as $type) {
            $ah = AsianHandicap::where('match_game_id', $match->id)
                ->where('type', $type)
                ->first();

            if ($ah) {
                $result[$type] = [
                    'home' => $this->passRateForTeam(
                        $match->home_team_id,
                        $match->league_id,
                        $match->season,
                        $ah->home_handicap,
                        'home'
                    ),
                    'away' => $this->passRateForTeam(
                        $match->away_team_id,
                        $match->league_id,
                        $match->season,
                        $ah->away_handicap,
                        'away'
                    ),
                ];
            }
        }

        return $result;
    }

    private function passRateForTeam($teamId, $leagueId, $season, $handicap, $field)
    {
        // Берём все завершённые матчи команды в данной лиге и сезоне
        $query = MatchGame::where(function ($q) use ($teamId, $field) {
            if ($field === 'home') {
                $q->where('home_team_id', $teamId);
            } else {
                $q->where('away_team_id', $teamId);
            }
        })
            ->where('league_id', $leagueId)
            ->where('season', $season)
            ->whereNotNull('home_score')
            ->whereNotNull('away_score');

        $matches = $query->get();

        if ($matches->isEmpty()) {
            return null;
        }

        $passed = 0;
        foreach ($matches as $match) {
            $isHome = ($match->home_team_id == $teamId);
            $goalsFor = $isHome ? $match->home_score : $match->away_score;
            $goalsAgainst = $isHome ? $match->away_score : $match->home_score;
            $diff = $goalsFor - $goalsAgainst;

            // Учитываем знак форы: для гостей фора обычно противоположная
            $handicapValue = $isHome ? $handicap : -$handicap;

            // Проход: diff + handicapValue > 0 (если =0 – возврат, считаем как не проход)
            if ($diff + $handicapValue > 0) {
                $passed++;
            }
        }

        return $passed / $matches->count();
    }
}
