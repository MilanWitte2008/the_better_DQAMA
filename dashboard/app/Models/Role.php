<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    // De rollen staan in de tabel 'rol'.
    protected $table = 'rol';

    // Elke rol heeft een eigen nummer: idrol.
    protected $primaryKey = 'idrol';

    public function displayName(): string
    {
        return in_array(strtolower((string) $this->naam), ['coach', 'docent'], true)
            ? 'Docent' : ($this->naam ?? 'Naamloze rol');
    }

    // Een rol kan bij meerdere gebruikers horen.
    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'rol_idrol', 'idrol');
    }

    // De rol-tabel heeft geen automatische tijdstempels.
    public $timestamps = false;
}
