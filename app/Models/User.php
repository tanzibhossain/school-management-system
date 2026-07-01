<?php

namespace App\Models;

use App\Modules\User\Models\LoginHistory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /** @return HasMany<LoginHistory> */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    /** @param Builder<User> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<User> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Token abilities granted to each role.
     *
     * @return array<int, string>
     */
    public static function abilitiesForRole(string $role): array
    {
        return match ($role) {
            'super_admin', 'admin' => ['*'],
            'teacher'              => ['teacher:marks', 'teacher:routine', 'teacher:leave', 'teacher:profile'],
            'accountant'           => ['accountant:payment', 'accountant:report', 'accountant:profile'],
            'librarian'            => ['librarian:books', 'librarian:profile'],
            'receptionist'         => ['receptionist:admission', 'receptionist:profile'],
            'student'              => ['student:profile', 'student:result', 'student:routine'],
            'parent'               => ['parent:view'],
            default                => ['student:profile'],
        };
    }
}
