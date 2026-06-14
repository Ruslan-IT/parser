<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class League extends Model
{

    use HasFactory;

    protected $fillable = [
        'name',
        'country',
    ];


    public function matches()
    {
        return $this->hasMany(MatchGame::class);
    }
}
