<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['match_id', 'player_id', 'points', 'payload'])]
class PlayerMatchStat extends Model
{
    protected function casts(): array
    {
        return ['points' => 'decimal:2', 'payload' => 'array'];
    }
}
