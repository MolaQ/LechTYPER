<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_id', 'season_id', 'round_number', 'opponent', 'lech_home', 'scheduled_at', 'result_home', 'result_away', 'status'])]
class LechMatch extends Model
{
    protected function casts(): array
    {
        return [
            'lech_home' => 'boolean',
            'scheduled_at' => 'datetime',
        ];
    }

    public function isTypingClosed(): bool
    {
        return $this->scheduled_at->isPast();
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function bonusQuestions(): HasMany
    {
        return $this->hasMany(BonusQuestion::class, 'match_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'match_id');
    }

    public function h2hFixtures(): HasMany
    {
        return $this->hasMany(H2hFixture::class, 'match_id');
    }
}
