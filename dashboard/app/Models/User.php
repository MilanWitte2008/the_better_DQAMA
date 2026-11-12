<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

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

    public function rollen(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'lid_rol', 'lid_idlid', 'rol_idrol', 'idlid', 'idrol');
    }

    /** @return array<int, int> */
    public function roleIds(): array
    {
        if (Schema::hasTable('lid_rol')) {
            $roleIds = $this->rollen()->pluck('rol.idrol')->map(fn ($roleId): int => (int) $roleId)->all();
            if ($roleIds !== []) {
                return $roleIds;
            }
        }

        return $this->rol_idrol ? [(int) $this->rol_idrol] : [];
    }

    /** @param array<int, string> $roleNames */
    public function hasAnyRole(array $roleNames): bool
    {
        $assignedNames = Schema::hasTable('lid_rol')
            ? $this->rollen()->pluck('rol.naam')->map(fn ($roleName): string => strtolower((string) $roleName))->all()
            : [];
        $primaryRoleName = strtolower((string) $this->rol?->naam);
        if ($primaryRoleName !== '') {
            $assignedNames[] = $primaryRoleName;
        }

        return array_intersect(array_map('strtolower', $roleNames), $assignedNames) !== [];
    }

    /** @return array<int, string> Dashboarden die bij alle toegewezen rollen horen. */
    public function dashboardRoleKeys(): array
    {
        $primaryRoleName = strtolower((string) $this->rol?->naam);
        $roleNames = $primaryRoleName !== '' ? [$primaryRoleName] : [];

        if (Schema::hasTable('lid_rol')) {
            $roleNames = array_merge(
                $roleNames,
                $this->rollen()->pluck('rol.naam')->map(fn ($name): string => strtolower((string) $name))->all(),
            );
        }

        $dashboardKeys = [];
        foreach (array_unique($roleNames) as $roleName) {
            $key = $this->dashboardForRole($roleName);
            if ($key !== null && ! in_array($key, $dashboardKeys, true)) {
                $dashboardKeys[] = $key;
            }
        }

        return $dashboardKeys;
    }

    // Kies het dashboard dat bij de rol hoort. Een onbekende rol krijgt geen dashboard.
    public function dashboardRoleKey(): ?string
    {
        return $this->dashboardRoleKeys()[0] ?? null;
    }

    private function dashboardForRole(string $roleName): ?string
    {
        return match ($roleName) {
            'student' => 'student',
            'coach' => 'coach',
            'docent' => 'docent',
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
