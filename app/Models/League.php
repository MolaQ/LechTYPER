<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'level', 'is_swiss'])]
class League extends Model
{
    protected function casts(): array
    {
        return ['is_swiss' => 'boolean'];
    }

    public function seasonLeagues(): HasMany
    {
        return $this->hasMany(SeasonLeague::class);
    }
}
