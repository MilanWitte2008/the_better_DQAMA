<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Dit project gebruikt de bestaande tabel 'lid' in plaats van Laravel's 'users'.
    protected $table = 'lid';

    // Primaire sleutel zoals die in de bestaande database is genoemd.
    protected $primaryKey = 'idlid';

    // De tabel bevat geen Laravel-created_at en updated_at kolommen.
    public $timestamps = false;

    // Verberg het opgeslagen wachtwoord wanneer een gebruiker als JSON wordt weergegeven.
    protected $hidden = ['wachtwoord'];

    // Vertel Laravel Authentication welke kolom het wachtwoord bevat.
    public function getAuthPasswordName(): string
    {
        return 'wachtwoord';
    }

    // Een lid hoort bij één rol via de foreign key rol_idrol.
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_idrol', 'idrol');
    }

    // Zet verschillende namen uit de database om naar vaste dashboardcodes.
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

    // Zet datumvelden om naar datumobjecten zodat Laravel ze makkelijk kan formatteren.
    protected function casts(): array
    {
        return [
            'aangemaakt_datum' => 'datetime',
        ];
    }
}
