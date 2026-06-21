<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsianHandicap extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_game_id',
        'type', // 'balanced' или 'purchase'
        'home_handicap',
        'away_handicap',
        'home_odds',
        'away_odds',
    ];

    public function matchGame()
    {
        return $this->belongsTo(MatchGame::class);
    }
}
