<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriteriaCategory extends Model
{
    public function predictions()
    {
        return $this->hasMany(MatchPrediction::class);
    }
}
