<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'rol';

    protected $primaryKey = 'idrol';

    public $timestamps = false;
}
