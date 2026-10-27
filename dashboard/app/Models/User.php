<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    protected function casts(): array
    {
        return [
            'aangemaakt_datum' => 'datetime',
        ];
    }
}
