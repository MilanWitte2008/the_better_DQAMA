<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamManagerDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('CREATE TABLE rol (idrol INTEGER PRIMARY KEY, naam TEXT)');
        DB::statement('CREATE TABLE lid (idlid INTEGER PRIMARY KEY, voornaam TEXT, achternaam TEXT, status TEXT, rol_idrol INTEGER)');
        DB::table('rol')->insert(['idrol' => 1, 'naam' => 'Teammanager']);
        DB::table('lid')->insert(['idlid' => 1, 'voornaam' => 'Test', 'achternaam' => 'Manager', 'status' => 'actief', 'rol_idrol' => 1]);
    }

    public function test_dashboard_opens_on_port_8000_when_group_tables_are_missing(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->get('http://localhost:8000/dashboard')
            ->assertOk()
            ->assertSee('Groepsbeheer is nog niet ingericht')
            ->assertSee('http://localhost:8000/dashboard', false);
    }

    public function test_existing_empty_group_tables_show_normal_empty_state(): void
    {
        DB::statement('CREATE TABLE groep (idgroep INTEGER PRIMARY KEY, groepnaam TEXT, status TEXT)');
        DB::statement('CREATE TABLE groep_has_lid (idgroep INTEGER, idlid INTEGER)');
        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Geen groepen gekoppeld')
            ->assertDontSee('Groepsbeheer is nog niet ingericht');
    }
}
