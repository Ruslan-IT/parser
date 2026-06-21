<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

}
