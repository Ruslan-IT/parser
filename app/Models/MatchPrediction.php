<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchPrediction extends Model
{

    use HasFactory;

    protected $fillable = [
        'match_game_id',
        'criteria_id',
        'category_id',
        'is_average',
        'prob_home',
        'prob_draw',
        'prob_away',
        'eff_home',
        'eff_draw',
        'eff_away',
        'handicap_home_prob',
        'handicap_away_prob',
        'handicap_home_eff',
        'handicap_away_eff',
    ];


    public function matchGame()
    {
        return $this->belongsTo(MatchGame::class);
    }
    public function category()
    {
        return $this->belongsTo(CriteriaCategory::class);
    }
}
