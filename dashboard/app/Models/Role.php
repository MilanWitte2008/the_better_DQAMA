<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // De rollen staan in de bestaande tabel 'rol'.
    protected $table = 'rol';

    // Naam van de primaire sleutel in die tabel.
    protected $primaryKey = 'idrol';

    // De rol-tabel heeft geen automatische tijdstempels.
    public $timestamps = false;
}
