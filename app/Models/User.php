<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $x_username
 * @property string $email
 * @property string $role
 * @property string|null $x_id
 * @property bool $is_premium
 * @property Carbon|null $premium_until
 * @property string|null $premium_source
 * @property bool $must_change_password
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_login_at
 * @property string $password
 * @property string|null $temporary_password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'x_username', 'email', 'password', 'temporary_password', 'role', 'x_id', 'must_change_password', 'is_premium', 'premium_until', 'premium_source', 'last_login_at'])]
#[Hidden(['password', 'temporary_password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'temporary_password' => 'encrypted',
            'must_change_password' => 'boolean',
            'is_premium' => 'boolean',
            'premium_until' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    public function hasActivePremium(): bool
    {
        return $this->is_premium && ($this->premium_until === null || $this->premium_until->isToday() || $this->premium_until->isFuture());
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
