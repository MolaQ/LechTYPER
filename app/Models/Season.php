<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'starts_at', 'ends_at', 'status', 'rounds'])]
class Season extends Model
{
    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function seasonLeagues(): HasMany
    {
        return $this->hasMany(SeasonLeague::class);
    }
}
