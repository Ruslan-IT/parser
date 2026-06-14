<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{

    use HasFactory;

    protected $fillable = [
        'name',
    ];


    public function homeMatches()
    {
        return $this->hasMany(MatchGame::class, 'home_team_id');
    }

    public function awayMatches()
    {
        return $this->hasMany(MatchGame::class, 'away_team_id');
    }
}
