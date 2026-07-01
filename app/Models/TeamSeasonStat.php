<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamSeasonStat extends Model
{


    use HasFactory;

    protected $table = 'team_season_stats';

    protected $fillable = [
        'team_id',
        'league_id',
        'season',
        'matches_total',
        'matches_home',
        'matches_away',
        'points_total',
        'points_home',
        'points_away',
        'goals_scored_total',
        'goals_scored_home',
        'goals_scored_away',
        'goals_conceded_total',
        'goals_conceded_home',
        'goals_conceded_away',
        'goals_diff_total',
        'goals_diff_home',
        'goals_diff_away',
        'points_last5',
        'points_last5_home',
        'points_last5_away',
        'goals_scored_last5',
        'goals_conceded_last5',
    ];
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
    public function league()
    {
        return $this->belongsTo(League::class);
    }
}
