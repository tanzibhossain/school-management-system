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
     * NOTE: the wildcard entries (e.g. 'teacher:*') were added alongside the
     * pre-existing narrow ones — Sanctum's tokenCan() is a literal string match
     * (only the bare '*' ability is special-cased), so a route gated on
     * `ability:teacher:*` never actually matched a token that only carried
     * 'teacher:marks' etc. That silently broke every teacher/staff self-service
     * route in Leave and Loan for real logins (only test tokens created directly
     * via createToken(['teacher:*']) ever passed). 'staff:*' is granted to every
     * employee-type role (teacher/accountant/librarian/receptionist) since all
     * of them are backed by a Staff row and are the intended audience for
     * Staff-module and Payroll-module self-service ("own record") endpoints.
     *
     * @return array<int, string>
     */
    public static function abilitiesForRole(string $role): array
    {
        return match ($role) {
            'super_admin', 'admin' => ['*'],
            'teacher'              => ['teacher:*', 'staff:*', 'teacher:marks', 'teacher:routine', 'teacher:leave', 'teacher:profile'],
            'accountant'           => ['accountant:*', 'staff:*', 'accountant:payment', 'accountant:report', 'accountant:profile'],
            'librarian'            => ['librarian:*', 'staff:*', 'librarian:books', 'librarian:profile'],
            'receptionist'         => ['receptionist:*', 'staff:*', 'receptionist:admission', 'receptionist:profile'],
            'student'              => ['student:*', 'student:profile', 'student:result', 'student:routine'],
            'parent'               => ['parent:*', 'parent:view'],
            default                => ['student:profile'],
        };
    }
}
