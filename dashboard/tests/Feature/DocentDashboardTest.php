<?php

namespace Tests\Feature;

use App\DocentDashboard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class DocentDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Meerdere requests delen in deze tests een sessieobject; voorkom dubbele JSON-deserialisatie.
        config(['session.serialization' => 'php']);
        $this->travelTo(now()->setDate(2026, 11, 4)->startOfDay());
        foreach ([
            'rol (idrol INTEGER PRIMARY KEY, naam TEXT)',
            'lid (idlid INTEGER PRIMARY KEY, voornaam TEXT, achternaam TEXT, email TEXT, wachtwoord TEXT, aangemaakt_datum TEXT, status TEXT, rol_idrol INTEGER)',
            'coach (idcoach INTEGER PRIMARY KEY, lid_idlid INTEGER)',
            'student (idstudent INTEGER PRIMARY KEY, lid_idlid INTEGER, klas TEXT, opleiding TEXT, studentnummer TEXT, startdatum TEXT)',
            'student_coach (student_idstudent INTEGER, coach_idcoach INTEGER, status TEXT, startdatum TEXT, einddatum TEXT)',
            'leeruitkomsten (leeruitkomsten_id INTEGER PRIMARY KEY, titel TEXT, code TEXT)',
            'student_leeruitkomsten (id_leeruitkomsten INTEGER PRIMARY KEY AUTOINCREMENT, opmerking TEXT, student_idstudent INTEGER, leeruitkomsten_leeruitkomsten_id INTEGER, status TEXT, vooruitgang INTEGER, laatste_update TEXT)',
            'begeleidingsnotitie (id_notitie INTEGER PRIMARY KEY AUTOINCREMENT, idstudent INTEGER, idcoach INTEGER, inhoud TEXT, datum TEXT, type TEXT)',
            'gesprekken (idgesprek INTEGER PRIMARY KEY AUTOINCREMENT, student_idstudent INTEGER, datum TEXT, onderwerp TEXT, inhoud TEXT, afspraken TEXT)',
        ] as $definition) {
            DB::statement('CREATE TABLE '.$definition);
        }
        DB::table('rol')->insert([['idrol' => 1, 'naam' => 'Docent'], ['idrol' => 2, 'naam' => 'Student']]);
        foreach ([1 => 'Docent', 2 => 'Andere docent', 11 => 'Ada', 12 => 'Bram', 13 => 'Privé', 14 => 'Verlopen', 15 => 'Toekomst'] as $id => $name) {
            DB::table('lid')->insert(['idlid' => $id, 'voornaam' => $name, 'achternaam' => 'Voorbeeld', 'email' => $id.'@example.test', 'status' => 'actief', 'rol_idrol' => $id < 10 ? 1 : 2]);
        }
        DB::table('coach')->insert([['idcoach' => 1, 'lid_idlid' => 1], ['idcoach' => 2, 'lid_idlid' => 2]]);
        foreach ([11, 12, 13, 14, 15] as $id) {
            DB::table('student')->insert(['idstudent' => $id, 'lid_idlid' => $id, 'klas' => $id === 11 ? 'SD2A' : 'SD2B', 'opleiding' => 'Software Development', 'studentnummer' => 'S'.$id]);
            DB::table('student_coach')->insert(['student_idstudent' => $id, 'coach_idcoach' => $id === 13 ? 2 : 1, 'status' => 'actief', 'startdatum' => $id === 15 ? '2027-01-01' : '2026-01-01', 'einddatum' => $id === 14 ? '2026-10-01' : null]);
        }
        DB::table('leeruitkomsten')->insert([['leeruitkomsten_id' => 1, 'titel' => 'Samenwerken', 'code' => 'LO1'], ['leeruitkomsten_id' => 2, 'titel' => 'Programmeren', 'code' => 'LO2']]);
        DB::table('student_leeruitkomsten')->insert([
            ['student_idstudent' => 11, 'leeruitkomsten_leeruitkomsten_id' => 1, 'status' => 'Behaald', 'laatste_update' => '2026-11-03 10:00:00'],
            ['student_idstudent' => 11, 'leeruitkomsten_leeruitkomsten_id' => 2, 'status' => 'Bezig', 'laatste_update' => '2026-11-03 10:00:00'],
            ['student_idstudent' => 13, 'leeruitkomsten_leeruitkomsten_id' => 1, 'status' => 'Behaald', 'laatste_update' => '2026-11-03 10:00:00'],
        ]);
        DB::table('begeleidingsnotitie')->insert([
            ['idstudent' => 11, 'idcoach' => 1, 'inhoud' => '<script>alert(1)</script>', 'datum' => '2026-11-01 10:00:00', 'type' => 'Voortgang'],
            ['idstudent' => 13, 'idcoach' => 1, 'inhoud' => 'Verborgen notitie', 'datum' => '2026-11-01 10:00:00', 'type' => 'Privé'],
            ['idstudent' => 11, 'idcoach' => 2, 'inhoud' => 'Andere auteur', 'datum' => '2026-11-01 10:00:00', 'type' => 'Privé'],
        ]);
        DB::table('gesprekken')->insert([
            ['student_idstudent' => 11, 'datum' => '2026-11-05 10:00:00', 'onderwerp' => 'Volgende stap'],
            ['student_idstudent' => 11, 'datum' => '2026-10-01 10:00:00', 'onderwerp' => 'Kennismaking'],
            ['student_idstudent' => 13, 'datum' => '2026-11-05 10:00:00', 'onderwerp' => 'Geheim gesprek'],
        ]);
    }

    public function test_real_statistics_are_scoped_to_current_assignments(): void
    {
        $data = app(DocentDashboard::class)->data(User::findOrFail(1), Request::create('/dashboard'));
        $this->assertSame([11, 12], $data['students']->pluck('idstudent')->sort()->values()->all());
        $this->assertSame(50, $data['students']->firstWhere('idstudent', 11)->progress);
        $this->assertNull($data['students']->firstWhere('idstudent', 12)->progress);
        $this->assertSame(1, $data['achieved']);
        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['attentionCount']);
        $this->assertSame(1, $data['notesCount']);
        $this->assertSame(1, $data['upcomingCount']);
        $this->assertCount(1, $data['recent']);
    }

    public function test_search_class_attention_and_detail_cannot_expand_access(): void
    {
        $service = app(DocentDashboard::class);
        $user = User::findOrFail(1);
        $data = $service->data($user, Request::create('/dashboard?q=ADA&klas=SD2A&student=13'));
        $this->assertSame([11], $data['filtered']->pluck('idstudent')->values()->all());
        $this->assertNull($data['selected']);
        $data = $service->data($user, Request::create('/dashboard?filter=aandacht'));
        $this->assertSame([12], $data['filtered']->pluck('idstudent')->values()->all());
        $data = $service->data($user, Request::create('/dashboard?q[]=invalid&student[]=13'));
        $this->assertNull($data['selected']);
        $this->assertCount(2, $data['filtered']);
    }

    public function test_dashboard_renders_escaped_records_and_student_detail(): void
    {
        $response = $this->actingAs(User::findOrFail(1))->get('/dashboard?student=11');
        $response->assertOk()->assertSee('Samenwerken')->assertSee('Volgende stap')->assertSee('Kennismaking')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Verborgen notitie')->assertDontSee('Andere auteur')->assertDontSee('Geheim gesprek');
        if (getenv('DOCENT_PREVIEW')) {
            file_put_contents(getenv('DOCENT_PREVIEW'), $response->getContent());
        }
    }

    public function test_missing_coach_profile_and_empty_results_have_honest_states(): void
    {
        DB::table('coach')->where('lid_idlid', 1)->delete();
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertOk()->assertSee('nog geen coachprofiel')->assertSee('Nog geen studenten gekoppeld');
    }

    public function test_inactive_docent_is_forbidden_and_guests_need_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        DB::table('lid')->where('idlid', 1)->update(['status' => 'inactief']);
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertForbidden();
    }

    public function test_docent_can_create_edit_and_delete_own_notes(): void
    {
        $this->actingAs(User::findOrFail(1));
        $this->post('/docent/notities', ['idstudent' => 11, 'type' => 'Notitie', 'inhoud' => 'Nieuwe notitie'])->assertRedirect('/dashboard#notities');
        $note = DB::table('begeleidingsnotitie')->where('inhoud', 'Nieuwe notitie')->first();
        $this->assertSame(1, $note->idcoach);
        $this->put('/docent/notities/'.$note->id_notitie, ['type' => 'Voortgang', 'inhoud' => 'Bijgewerkt'])->assertRedirect();
        $this->assertDatabaseHas('begeleidingsnotitie', ['id_notitie' => $note->id_notitie, 'inhoud' => 'Bijgewerkt']);
        $this->delete('/docent/notities/'.$note->id_notitie)->assertRedirect();
        $this->assertDatabaseMissing('begeleidingsnotitie', ['id_notitie' => $note->id_notitie]);
    }

    public function test_docent_can_manage_meetings_and_change_outcomes_and_class(): void
    {
        $this->actingAs(User::findOrFail(1));
        $input = ['student_idstudent' => 11, 'onderwerp' => 'Evaluatie', 'datum' => '2026-11-10T10:30', 'inhoud' => 'Verslag', 'afspraken' => 'Vervolg'];
        $this->post('/docent/gesprekken', $input)->assertRedirect('/dashboard#gesprekken');
        $id = DB::table('gesprekken')->where('onderwerp', 'Evaluatie')->value('idgesprek');
        $this->put('/docent/gesprekken/'.$id, array_replace($input, ['onderwerp' => 'Verplaatst', 'datum' => '2026-11-11T12:00']))->assertRedirect();
        $this->assertDatabaseHas('gesprekken', ['idgesprek' => $id, 'onderwerp' => 'Verplaatst']);
        $this->put('/docent/leeruitkomsten/2', ['status' => 'Behaald', 'opmerking' => 'Goed gedaan'])->assertRedirect('/dashboard?student=11#student-detail');
        $this->assertDatabaseHas('student_leeruitkomsten', ['id_leeruitkomsten' => 2, 'status' => 'Behaald', 'vooruitgang' => 100]);
        $this->get('/dashboard?student=11')->assertOk()->assertSee('100%')->assertSee('Goed gedaan');
        $this->put('/docent/studenten/11', ['klas' => 'SD3A', 'opleiding' => 'ICT'])->assertRedirect();
        $this->assertDatabaseHas('student', ['idstudent' => 11, 'klas' => 'SD3A']);
        $this->delete('/docent/gesprekken/'.$id)->assertRedirect();
        $this->assertDatabaseMissing('gesprekken', ['idgesprek' => $id]);
    }

    public function test_writes_reject_other_coaches_students_authors_and_expired_assignments(): void
    {
        $this->actingAs(User::findOrFail(1));
        $this->post('/docent/notities', ['idstudent' => 13, 'type' => 'Notitie', 'inhoud' => 'Niet toegestaan'])->assertForbidden();
        $this->put('/docent/notities/3', ['type' => 'Notitie', 'inhoud' => 'Overnemen'])->assertNotFound();
        $this->delete('/docent/notities/3')->assertNotFound();
        $this->put('/docent/gesprekken/3', ['onderwerp' => 'Overnemen', 'datum' => '2026-11-10T10:30'])->assertNotFound();
        $this->delete('/docent/gesprekken/3')->assertNotFound();
        $this->put('/docent/leeruitkomsten/3', ['status' => 'Behaald'])->assertNotFound();
        $this->put('/docent/studenten/14', ['klas' => 'Nieuw'])->assertForbidden();
        DB::table('student_coach')->where('student_idstudent', 11)->update(['status' => 'inactief']);
        $this->delete('/docent/notities/1')->assertNotFound();
        $this->assertDatabaseHas('begeleidingsnotitie', ['id_notitie' => 3, 'inhoud' => 'Andere auteur']);
    }

    public function test_invalid_input_is_preserved_and_never_written(): void
    {
        $this->actingAs(User::findOrFail(1));
        $this->from('/dashboard#notities')->post('/docent/notities', ['_form' => 'note-new', 'idstudent' => 11, 'type' => 'Notitie', 'inhoud' => ''])->assertSessionHasErrors('inhoud')->assertSessionHasInput('_form', 'note-new');
        $this->put('/docent/leeruitkomsten/1', ['status' => 'Onbekend'])->assertSessionHasErrors('status');
        $this->post('/docent/gesprekken', ['student_idstudent' => 11, 'onderwerp' => 'Ongeldig', 'datum' => 'morgen'])->assertSessionHasErrors('datum');
        $this->assertDatabaseMissing('gesprekken', ['onderwerp' => 'Ongeldig']);
        $this->put('/docent/studenten/11', ['klas' => str_repeat('x', 21)])->assertSessionHasErrors('klas');
    }

    public function test_other_roles_and_inactive_docents_cannot_write(): void
    {
        $this->actingAs(User::findOrFail(11))->put('/docent/studenten/11', ['klas' => 'Nieuw'])->assertForbidden();
        DB::table('lid')->where('idlid', 1)->update(['status' => 'inactief']);
        $this->actingAs(User::findOrFail(1))->delete('/docent/notities/1')->assertForbidden();
    }

    public function test_validation_messages_render_with_the_correct_form_reopened(): void
    {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['inhoud' => 'Vul een notitie in.']));
        $this->actingAs(User::findOrFail(1))->withSession(['errors' => $errors, '_old_input' => ['_form' => 'note-new', 'inhoud' => '']])
            ->get('/dashboard')->assertOk()->assertSee('role="alert"', false)->assertSee('Vul een notitie in.');
    }

    public function test_student_actions_are_visible_and_edit_form_is_open(): void
    {
        $response = $this->actingAs(User::findOrFail(1))->get('/dashboard?student=11');
        $response->assertOk()->assertSee('Bewerken')->assertSee('Verwijderen')->assertSee('class="edit-panel" open', false);
    }

    public function test_removing_student_keeps_account_and_other_coach_assignment(): void
    {
        DB::table('student_coach')->insert(['student_idstudent' => 11, 'coach_idcoach' => 2, 'status' => 'actief']);
        $this->actingAs(User::findOrFail(1))->delete('/docent/studenten/11')->assertRedirect('/dashboard#studenten');
        $this->assertDatabaseHas('student', ['idstudent' => 11]);
        $this->assertDatabaseHas('lid', ['idlid' => 11]);
        $this->assertDatabaseHas('student_coach', ['student_idstudent' => 11, 'coach_idcoach' => 1, 'status' => 'inactief']);
        $this->assertDatabaseHas('student_coach', ['student_idstudent' => 11, 'coach_idcoach' => 2, 'status' => 'actief']);
        $this->delete('/docent/studenten/13')->assertForbidden();
        $this->put('/docent/studenten/11', ['klas' => 'Geen toegang'])->assertForbidden();
    }

    public function test_outcomes_can_be_assigned_once_then_edited(): void
    {
        $this->actingAs(User::findOrFail(1))->get('/dashboard?student=12')->assertOk()->assertSee('Leeruitkomst toevoegen');
        $input = ['leeruitkomsten_leeruitkomsten_id' => 1];
        $this->post('/docent/studenten/12/leeruitkomsten', $input)->assertRedirect('/dashboard?student=12#student-detail');
        $this->post('/docent/studenten/12/leeruitkomsten', $input)->assertRedirect();
        $this->assertSame(1, DB::table('student_leeruitkomsten')->where('student_idstudent', 12)->count());
        $id = DB::table('student_leeruitkomsten')->where('student_idstudent', 12)->value('id_leeruitkomsten');
        $this->put('/docent/leeruitkomsten/'.$id, ['status' => 'Behaald', 'opmerking' => 'Afgerond'])->assertRedirect();
        $this->assertDatabaseHas('student_leeruitkomsten', ['id_leeruitkomsten' => $id, 'status' => 'Behaald']);
        $this->post('/docent/studenten/13/leeruitkomsten', $input)->assertForbidden();
        $this->post('/docent/studenten/12/leeruitkomsten', ['leeruitkomsten_leeruitkomsten_id' => 999])->assertSessionHasErrors('leeruitkomsten_leeruitkomsten_id');
    }

    public function test_meeting_datetime_with_seconds_is_saved_in_database_format(): void
    {
        $this->actingAs(User::findOrFail(1))->post('/docent/gesprekken', ['student_idstudent' => 11, 'onderwerp' => 'Met seconden', 'datum' => '2026-11-10T10:30:00'])->assertRedirect();
        $this->assertDatabaseHas('gesprekken', ['onderwerp' => 'Met seconden', 'datum' => '2026-11-10 10:30:00']);
    }

    private function newStudentInput(array $overrides = []): array
    {
        return array_replace(['voornaam' => 'Nieuwe', 'achternaam' => 'Student', 'email' => 'nieuw@example.test', 'password' => 'veilig-test-wachtwoord', 'password_confirmation' => 'veilig-test-wachtwoord', 'klas' => 'SD1', 'opleiding' => 'ICT'], $overrides);
    }

    public function test_docent_can_create_student_account_profile_and_assignment(): void
    {
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertOk()->assertSee('Student toevoegen');
        $response = $this->post('/docent/studenten', $this->newStudentInput(['rol_idrol' => 1]));
        $response->assertRedirect();
        $member = User::where('email', 'nieuw@example.test')->firstOrFail();
        $this->assertSame(2, $member->rol_idrol);
        $this->assertTrue(Hash::check('veilig-test-wachtwoord', $member->wachtwoord));
        $studentId = DB::table('student')->where('lid_idlid', $member->getKey())->value('idstudent');
        $this->assertDatabaseHas('student_coach', ['student_idstudent' => $studentId, 'coach_idcoach' => 1, 'status' => 'actief']);
        $this->get('/dashboard?student='.$studentId)->assertOk()->assertSee('nieuw@example.test')->assertDontSee($member->wachtwoord);
        $this->app['auth']->forgetGuards();
        $this->post('/login', ['email' => 'nieuw@example.test', 'password' => 'veilig-test-wachtwoord'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($member);
    }

    public function test_invalid_student_creation_writes_nothing(): void
    {
        $this->actingAs(User::findOrFail(1));
        $this->post('/docent/studenten', $this->newStudentInput(['email' => '11@example.test', 'password_confirmation' => 'anders']))->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseCount('lid', 7);
        $this->assertDatabaseCount('student', 5);
        $this->assertDatabaseCount('student_coach', 5);
    }

    public function test_only_active_docents_with_profiles_can_create_students(): void
    {
        $this->actingAs(User::findOrFail(11))->post('/docent/studenten', $this->newStudentInput())->assertForbidden();
        DB::table('coach')->where('lid_idlid', 1)->delete();
        $this->actingAs(User::findOrFail(1))->post('/docent/studenten', $this->newStudentInput())->assertForbidden();
        $this->assertDatabaseMissing('lid', ['email' => 'nieuw@example.test']);
    }
}
