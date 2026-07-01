<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriteriaValue extends Model
{
    protected $table = 'criteria_values'; // если таблица называется так

    protected $fillable = [
        'match_game_id',
        'criteria_1',
        'criteria_2',
        'criteria_3',
        'criteria_4',
        'criteria_5',          // пока оставляем, позже можно удалить
        'criteria_6',
        'handicap_balanced_home',
        'handicap_balanced_away',
        'handicap_purchase_home',
        'handicap_purchase_away',
    ];


    public function matchGame()
    {
        return $this->belongsTo(MatchGame::class);
    }
}
