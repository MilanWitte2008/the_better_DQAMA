<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'lid';

    protected $primaryKey = 'idlid';

    public $timestamps = false;

    protected $hidden = ['wachtwoord'];

    public function getAuthPasswordName(): string
    {
        return 'wachtwoord';
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_idrol', 'idrol');
    }

    public function dashboardRoleKey(): ?string
    {
        return match (strtolower((string) $this->rol?->naam)) {
            'student' => 'student',
            'coach', 'docent' => 'docent',
            'admin', 'applicatiebeheerder', 'beheerder' => 'beheerder',
            'teammanager', 'teamleider', 'manager' => 'teamleider',
            default => null,
        };
    }

    protected function casts(): array
    {
        return [
            'aangemaakt_datum' => 'datetime',
        ];
    }
}
