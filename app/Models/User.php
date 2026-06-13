<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ALUMNI = 'alumni';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_ALUMNI,
    ];

    public const ADMIN_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'middlename',
        'lastname',
        'email',
        'password',
        'alumni_idnum',
        'student_idnum',
        'birthdate',
        'phone',
        'sex',
        'civil_status',
        'department',
        'degree',
        'course',
        'major',
        'dt_grad',
        'year_graduated',
        'employed_after_grad',
        'industry',
        'current_position',
        'company',
        'location',
        'salary_range',
        'linkedin_profile',
        'skills',
        'achievements',
        'has_scholarship',
        'scholarship_name',
        'scholarship_year_awarded',
        'scholarship_helped_get_job',
        'role',
        'is_admin',
        'is_batch_imported',
        'batch_imported_at',
        'alumni_import_batch_id',
        'email_verified_at',
        'data_privacy_consent',
        'data_privacy_consented_at',
        'newsletter_opt_in',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'birthdate' => 'date',
            'dt_grad' => 'date',
            'is_admin' => 'boolean',
            'is_batch_imported' => 'boolean',
            'batch_imported_at' => 'datetime',
            'has_scholarship' => 'boolean',
            'scholarship_year_awarded' => 'integer',
            'scholarship_helped_get_job' => 'boolean',
            'data_privacy_consent' => 'boolean',
            'data_privacy_consented_at' => 'datetime',
            'newsletter_opt_in' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $role = $user->normalizedRole($user->role);

            if ($user->isDirty('is_admin') && ! $user->isDirty('role')) {
                $role = (bool) $user->is_admin ? self::ROLE_ADMIN : self::ROLE_ALUMNI;
            } elseif ($role === null) {
                $role = (bool) $user->is_admin ? self::ROLE_ADMIN : self::ROLE_ALUMNI;
            }

            $user->role = $role;
            $user->is_admin = in_array($role, self::ADMIN_ROLES, true);
        });
    }

    public function tracerRecords(): HasMany
    {
        return $this->hasMany(TracerRecord::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(AlumniImportBatch::class, 'alumni_import_batch_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, self::ADMIN_ROLES, true) || (bool) $this->is_admin;
    }

    public function isAlumni(): bool
    {
        return $this->role === self::ROLE_ALUMNI && ! $this->isAdmin();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super admin',
            self::ROLE_ADMIN => 'Admin',
            default => 'Alumni',
        };
    }

    public function scopeAdministrators(Builder $query): Builder
    {
        return $query->whereIn('role', self::ADMIN_ROLES);
    }

    public function scopeAlumni(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ALUMNI);
    }

    public function scopeRegisteredAlumniAccount(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $registeredQuery): void {
                $registeredQuery
                    ->where('is_batch_imported', false)
                    ->orWhereNull('is_batch_imported');
            })
            ->where('email', 'not like', '%@oarl-batch.local');
    }

    protected function normalizedRole(mixed $role): ?string
    {
        if (! is_string($role)) {
            return null;
        }

        $role = trim($role);

        return in_array($role, self::ROLES, true) ? $role : null;
    }
}
