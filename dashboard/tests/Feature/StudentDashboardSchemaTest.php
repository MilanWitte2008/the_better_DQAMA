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
            'student (idstudent INTEGER PRIMARY KEY, lid_idlid INTEGER, studentnummer TEXT, klas TEXT, opleiding TEXT)',
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

    private function signInAsAdmin(): void
    {
        DB::table('rol')->insert(['idrol' => 2, 'naam' => 'Admin']);
        DB::table('lid')->insert(['idlid' => 201, 'voornaam' => 'Beheerder', 'status' => 'actief', 'rol_idrol' => 2]);
        $this->actingAs(User::findOrFail(201));
    }

    public function test_admin_can_add_and_update_hours_visible_only_to_target_student(): void
    {
        $this->signInAsAdmin();
        $payload = ['datum' => today()->toDateString(), 'uren' => 3.5, 'omschrijving' => 'Projectwerk', 'status' => 'In afwachting'];
        $this->post(route('beheer.members.hours.save', 101), $payload)->assertSessionHasNoErrors()->assertRedirect(route('beheer.members.edit', 101));
        $id = DB::table('urenregistratie')->where('omschrijving', 'Projectwerk')->value('idurenregistratie');
        $this->post(route('beheer.members.hours.save', 101), array_merge($payload, ['entry_id' => $id, 'uren' => 4, 'status' => 'Goedgekeurd']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('urenregistratie', ['idurenregistratie' => $id, 'student_id' => 11, 'uren' => 4, 'status' => 'Goedgekeurd']);
        $this->get(route('beheer.members.edit', 101))->assertOk()->assertSee('Uren beheren')->assertSee('Projectwerk');
        $this->actingAs(User::findOrFail(101))->get('/dashboard')->assertOk()->assertSee('Projectwerk')->assertSee('<div class="hours-total">6 <span>', false);
        $this->actingAs(User::findOrFail(102))->get('/dashboard')->assertOk()->assertDontSee('Projectwerk');
    }

    public function test_hours_reject_invalid_values_unauthorized_users_and_another_students_entry(): void
    {
        $payload = ['datum' => today()->toDateString(), 'uren' => 3, 'omschrijving' => 'Projectwerk', 'status' => 'Goedgekeurd'];
        $this->actingAs(User::findOrFail(101))->post(route('beheer.members.hours.save', 101), $payload)->assertForbidden();
        $this->signInAsAdmin();
        $this->post(route('beheer.members.hours.save', 101), array_merge($payload, ['entry_id' => 12]))->assertNotFound();
        $this->post(route('beheer.members.hours.save', 101), array_merge($payload, ['uren' => -1]))->assertSessionHasErrors('uren');
        $this->post(route('beheer.members.hours.save', 101), array_merge($payload, ['datum' => today()->addDay()->toDateString()]))->assertSessionHasErrors('datum');
        $this->assertDatabaseCount('urenregistratie', 2);
    }

    public function test_admin_hours_support_member_schema(): void
    {
        DB::statement('ALTER TABLE urenregistratie ADD COLUMN lid_id INTEGER');
        $this->signInAsAdmin();
        $this->post(route('beheer.members.hours.save', 101), ['datum' => today()->toDateString(), 'uren' => 1.25, 'omschrijving' => 'Nieuwe uren', 'status' => 'Goedgekeurd'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('urenregistratie', ['lid_id' => 101, 'uren' => 1.25]);
        $this->actingAs(User::findOrFail(101))->get('/dashboard')->assertOk()->assertSee('Nieuwe uren');
        $this->actingAs(User::findOrFail(102))->get('/dashboard')->assertOk()->assertDontSee('Nieuwe uren');
    }

    public function test_admin_can_create_and_assign_phase_without_outcomes_and_without_duplicates(): void
    {
        (require database_path('migrations/2026_11_11_090141_create_student_phase_assignments_table.php'))->up();
        $this->actingAs(User::findOrFail(101))->post(route('beheer.members.phases.store', 101), ['phase_name' => 'Verdieping'])->assertForbidden();
        $this->signInAsAdmin();
        $this->post(route('beheer.members.phases.store', 101), ['phase_name' => 'Verdieping'])->assertSessionHasNoErrors()->assertRedirect(route('beheer.members.edit', 101));
        $id = DB::table('fase')->where('naam', 'Verdieping')->value('idfase');
        $this->post(route('beheer.members.phases.store', 101), ['phase_id' => $id])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('student_phase_assignments', 1);
        $this->post(route('beheer.members.phases.store', 101), ['phase_id' => 999])->assertSessionHasErrors('phase_id');
        $this->post(route('beheer.members.phases.store', 101), ['phase_id' => $id, 'phase_name' => 'Other'])->assertSessionHasErrors('phase_name');
        $this->get(route('beheer.members.edit', 101))->assertOk()->assertSee('Verdieping');
        $this->actingAs(User::findOrFail(101))->get('/dashboard')->assertOk()->assertSee('Verdieping');
        $this->actingAs(User::findOrFail(102))->get('/dashboard')->assertOk()->assertDontSee('Verdieping');
    }
}
