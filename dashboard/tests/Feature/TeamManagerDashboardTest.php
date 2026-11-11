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

    public function test_missing_group_tables_return_validation_instead_of_an_error_page(): void
    {
        $this->actingAs(User::findOrFail(1))->from('/dashboard')
            ->post('/teamleider/groepen', ['groepnaam' => 'ICT3'])
            ->assertRedirect('/dashboard')->assertSessionHasErrors('groepnaam');
    }

    public function test_group_creation_members_and_redirect_work_with_legacy_database(): void
    {
        $migration = require database_path('migrations/2026_11_10_125937_create_group_membership_tables.php');
        $migration->up();
        $migration->up();
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('CONCAT', fn (...$values) => implode('', $values));
        $pdo->sqliteCreateFunction('GREATEST', fn (...$values) => max($values));
        DB::statement('ALTER TABLE lid ADD COLUMN email TEXT');
        foreach ([
            'student (idstudent INTEGER PRIMARY KEY, lid_idlid INTEGER)',
            'coach (idcoach INTEGER PRIMARY KEY, lid_idlid INTEGER)',
            'student_coach (id INTEGER PRIMARY KEY, student_idstudent INTEGER, coach_idcoach INTEGER, status TEXT, startdatum TEXT, einddatum TEXT)',
            'student_leeruitkomsten (id_leeruitkomsten INTEGER PRIMARY KEY, student_idstudent INTEGER, leeruitkomsten_leeruitkomsten_id INTEGER, status TEXT)',
            'leeruitkomsten (leeruitkomsten_id INTEGER PRIMARY KEY, code TEXT, titel TEXT, omschrijving TEXT)',
            'reflectie (idreflectie INTEGER PRIMARY KEY, student_leeruitkomsten_id_leeruitkomsten INTEGER, datum TEXT)',
            'urenregistratie (student_id INTEGER, datum TEXT)',
        ] as $table) {
            DB::statement('CREATE TABLE '.$table);
        }
        DB::table('rol')->insert([['idrol' => 2, 'naam' => 'Student'], ['idrol' => 3, 'naam' => 'Coach']]);
        DB::table('lid')->insert([
            ['idlid' => 11, 'voornaam' => 'Student', 'status' => 'actief', 'rol_idrol' => 2],
            ['idlid' => 12, 'voornaam' => 'Docent', 'status' => 'actief', 'rol_idrol' => 3],
            ['idlid' => 13, 'voornaam' => 'Andere manager', 'status' => 'actief', 'rol_idrol' => 1],
        ]);
        $this->actingAs(User::findOrFail(1))->post('/teamleider/groepen', ['groepnaam' => 'ICT3'])->assertRedirect();
        $group = DB::table('groep')->value('idgroep');
        $this->assertDatabaseHas('groep_has_lid', ['idgroep' => $group, 'idlid' => 1]);
        $this->get('/dashboard?groep='.$group)->assertOk()->assertSee('ICT3');
        foreach ([11, 12, 12] as $member) {
            $this->post('/teamleider/groepen/'.$group.'/leden', ['lid_idlid' => $member])->assertRedirect();
        }
        $this->get('/dashboard?groep='.$group)->assertOk()->assertSee('Docent')->assertSee('Student');
        $this->get('/dashboard?groep='.$group)->assertOk();
        $this->assertDatabaseCount('student', 1);
        $this->assertDatabaseCount('coach', 1);
        $this->assertDatabaseCount('student_coach', 1);
        $this->actingAs(User::findOrFail(13))->get('/dashboard?groep='.$group)->assertNotFound();
        $this->actingAs(User::findOrFail(11))->post('/teamleider/groepen', ['groepnaam' => 'Verboden'])->assertForbidden();
        $this->assertDatabaseCount('groep', 1);
    }
}
