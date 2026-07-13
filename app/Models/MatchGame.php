<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\MatchPrediction;

class MatchGame extends Model
{

    use HasFactory;

    protected $fillable = [
        'league_id',
        'home_team_id',
        'away_team_id',
        'match_date',
        'home_score',
        'away_score',
        'betexplorer_id',
        'url',
        'odd_home',
        'odd_draw',
        'odd_away',
        'season',          // добавим позже
        'match_status',    // 'scheduled' или 'finished'

    ];

    protected $casts = [
        'match_date' => 'datetime',
    ];


    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
    public function asianHandicaps()
    {
        return $this->hasMany(AsianHandicap::class);
    }

    public function predictions()
    {
        return $this->hasMany(MatchPrediction::class);
    }
    public function criteriaValue()
    {
        return $this->hasOne(CriteriaValue::class);
    }
    public function matchPredictions()
    {
        return $this->hasMany(MatchPrediction::class);
    }

    public function averagePrediction()
    {
        return $this->hasOne(MatchPrediction::class)->where('is_average', true);
    }



}
