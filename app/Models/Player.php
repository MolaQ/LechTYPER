<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shirt_number', 'name', 'position', 'is_active'])]
class Player extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function teamPlayers(): HasMany
    {
        return $this->hasMany(TeamPlayer::class);
    }
}
