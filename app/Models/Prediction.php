<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'match_id', 'home_score', 'away_score', 'points_base', 'points_offensive', 'points_defensive_applied', 'total_points'])]
class Prediction extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(LechMatch::class, 'match_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(UserAnswer::class, 'user_id', 'user_id');
    }
}
