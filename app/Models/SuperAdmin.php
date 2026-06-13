<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SuperAdmin extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $table = 'super_admins';

    protected $fillable = [
        'id',
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return true;
    }

    public function isSuperAdmin(): bool
    {
        return true;
    }

    public function isAlumni(): bool
    {
        return false;
    }

    public function roleLabel(): string
    {
        return 'Super admin';
    }
}
