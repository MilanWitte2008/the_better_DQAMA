<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // De gebruikers staan in de tabel 'lid'.
    protected $table = 'lid';

    // Elke gebruiker heeft een eigen nummer: idlid.
    protected $primaryKey = 'idlid';

    // Deze tabel heeft geen created_at en updated_at. Laravel vult die dus niet in.
    public $timestamps = false;

    // Stuur het wachtwoord niet mee als we de gebruiker als JSON tonen.
    protected $hidden = ['wachtwoord'];

    // Laravel vindt het opgeslagen wachtwoord in de kolom wachtwoord.
    public function getAuthPasswordName(): string
    {
        return 'wachtwoord';
    }

    // Elke gebruiker hoort bij een rol. rol_idrol verwijst naar het nummer van die rol.
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_idrol', 'idrol');
    }

    // Kies het dashboard dat bij de rol hoort. Een onbekende rol krijgt geen dashboard.
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

    // Lees dit veld als een datum. Zo kunnen we bijvoorbeeld dag, maand en jaar tonen.
    protected function casts(): array
    {
        return [
            'aangemaakt_datum' => 'datetime',
        ];
    }
}
