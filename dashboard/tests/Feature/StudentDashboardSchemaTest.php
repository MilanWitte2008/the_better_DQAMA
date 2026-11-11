<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentDashboardSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('FIELD', fn ($value, ...$items) => array_search($value, $items, true) + 1);
        $pdo->sqliteCreateFunction('DATE_FORMAT', fn ($value, $format) => substr($value, 0, 7));
        foreach ([
            'rol (idrol INTEGER PRIMARY KEY, naam TEXT)',
            'lid (idlid INTEGER PRIMARY KEY, voornaam TEXT, achternaam TEXT, email TEXT, status TEXT, rol_idrol INTEGER)',
            'student (idstudent INTEGER PRIMARY KEY, lid_idlid INTEGER)',
            'fase (idfase INTEGER PRIMARY KEY, naam TEXT, volgorde INTEGER)',
            'leeruitkomsten (leeruitkomsten_id INTEGER PRIMARY KEY, titel TEXT, omschrijving TEXT, fase_idfase INTEGER)',
            'student_leeruitkomsten (id_leeruitkomsten INTEGER PRIMARY KEY, student_idstudent INTEGER, leeruitkomsten_leeruitkomsten_id INTEGER, status TEXT)',
            'urenregistratie (idurenregistratie INTEGER PRIMARY KEY, student_id INTEGER, datum TEXT, uren REAL, status TEXT, omschrijving TEXT)',
            'reflectie (idreflectie INTEGER PRIMARY KEY, student_leeruitkomsten_id_leeruitkomsten INTEGER, inhoud TEXT, datum TEXT)',
        ] as $table) {
            DB::statement('CREATE TABLE '.$table);
        }
        DB::table('rol')->insert(['idrol' => 1, 'naam' => 'Student']);
        foreach ([101 => 11, 102 => 12] as $member => $student) {
            DB::table('lid')->insert(['idlid' => $member, 'voornaam' => 'Student', 'email' => $member.'@example.test', 'status' => 'actief', 'rol_idrol' => 1]);
            DB::table('student')->insert(['idstudent' => $student, 'lid_idlid' => $member]);
            DB::table('student_leeruitkomsten')->insert(['id_leeruitkomsten' => $student, 'student_idstudent' => $student, 'leeruitkomsten_leeruitkomsten_id' => 1, 'status' => 'Behaald']);
            DB::table('urenregistratie')->insert(['idurenregistratie' => $student, 'student_id' => $student, 'datum' => today()->toDateString(), 'uren' => 2, 'status' => 'Goedgekeurd', 'omschrijving' => 'Uren van '.$member]);
            DB::table('reflectie')->insert(['idreflectie' => $student, 'student_leeruitkomsten_id_leeruitkomsten' => $student, 'inhoud' => 'Reflectie van '.$member, 'datum' => today()->toDateString()]);
        }
        DB::table('fase')->insert(['idfase' => 1, 'naam' => 'Start', 'volgorde' => 1]);
        DB::table('leeruitkomsten')->insert(['leeruitkomsten_id' => 1, 'titel' => 'Samenwerken', 'omschrijving' => 'Samen leren', 'fase_idfase' => 1]);
    }

    public function test_legacy_schema_renders_and_isolates_student_data(): void
    {
        $this->actingAs(User::findOrFail(101))->get('/dashboard')->assertOk()
            ->assertSee('Samenwerken')->assertSee('Reflectie van 101')->assertSee('<div class="hours-total">2 <span>', false)
            ->assertDontSee('Reflectie van 102')->assertDontSee('Uren van 102');
    }

    public function test_member_schema_renders_and_isolates_student_data(): void
    {
        DB::statement('CREATE TABLE leeruitkomsten_has_lid (leeruitkomsten_id INTEGER, lid_idlid INTEGER, status TEXT, PRIMARY KEY(leeruitkomsten_id,lid_idlid))');
        DB::statement('ALTER TABLE urenregistratie ADD COLUMN lid_id INTEGER');
        DB::statement('ALTER TABLE reflectie ADD COLUMN lid_idlid INTEGER');
        foreach ([101 => 11, 102 => 12] as $member => $student) {
            DB::table('leeruitkomsten_has_lid')->insert(['leeruitkomsten_id' => 1, 'lid_idlid' => $member, 'status' => 'Behaald']);
            DB::table('urenregistratie')->where('student_id', $student)->update(['lid_id' => $member]);
            DB::table('reflectie')->where('idreflectie', $student)->update(['lid_idlid' => $member]);
        }
        $this->actingAs(User::findOrFail(101))->get('/dashboard')->assertOk()
            ->assertSee('Samenwerken')->assertSee('Reflectie van 101')->assertSee('<div class="hours-total">2 <span>', false)
            ->assertDontSee('Reflectie van 102')->assertDontSee('Uren van 102');
    }

    public function test_student_without_profile_can_open_empty_dashboard(): void
    {
        DB::table('student')->where('lid_idlid', 101)->delete();
        $this->actingAs(User::findOrFail(101))->get('/dashboard')->assertOk()
            ->assertDontSee('Reflectie van 101')->assertDontSee('Reflectie van 102')
            ->assertDontSee('Uren van 101')->assertDontSee('Uren van 102');
    }
}
