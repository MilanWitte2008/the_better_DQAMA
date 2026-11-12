<?php

namespace Tests\Feature;

use App\DocentDashboard;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            'leeruitkomsten (leeruitkomsten_id INTEGER PRIMARY KEY, titel TEXT, code TEXT, omschrijving TEXT)',
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

    public function test_docent_can_approve_or_reject_only_their_own_pending_requests(): void
    {
        DB::statement('CREATE TABLE gesprekken_has_lid (gesprekken_idgesprek INTEGER, lid_idlid INTEGER)');
        $firstRequest = DB::table('gesprekken')->insertGetId([
            'student_idstudent' => 11,
            'datum' => '2026-11-13 10:00:00',
            'onderwerp' => DocentDashboard::MEETING_REQUEST_TOPIC,
            'inhoud' => 'Ik wil mijn voortgang bespreken.',
            'afspraken' => 'pending',
        ]);
        $olderApprovedRequest = DB::table('gesprekken')->insertGetId([
            'student_idstudent' => 11,
            'datum' => null,
            'onderwerp' => DocentDashboard::MEETING_REQUEST_TOPIC,
            'inhoud' => 'Oud verzoek zonder datum',
            'afspraken' => 'approved',
        ]);
        $secondRequest = DB::table('gesprekken')->insertGetId([
            'student_idstudent' => 13,
            'onderwerp' => DocentDashboard::MEETING_REQUEST_TOPIC,
            'inhoud' => 'Ik wil hulp met mijn leerdoel.',
            'afspraken' => 'pending',
        ]);
        $rejectedRequest = DB::table('gesprekken')->insertGetId([
            'student_idstudent' => 11,
            'onderwerp' => DocentDashboard::MEETING_REQUEST_TOPIC,
            'inhoud' => 'Deze afgekeurde aanvraag mag niet in de lijst staan.',
            'afspraken' => 'rejected',
        ]);
        DB::table('gesprekken_has_lid')->insert([
            ['gesprekken_idgesprek' => $firstRequest, 'lid_idlid' => 11],
            ['gesprekken_idgesprek' => $firstRequest, 'lid_idlid' => 1],
            ['gesprekken_idgesprek' => $olderApprovedRequest, 'lid_idlid' => 11],
            ['gesprekken_idgesprek' => $olderApprovedRequest, 'lid_idlid' => 1],
            ['gesprekken_idgesprek' => $secondRequest, 'lid_idlid' => 13],
            ['gesprekken_idgesprek' => $secondRequest, 'lid_idlid' => 2],
            ['gesprekken_idgesprek' => $rejectedRequest, 'lid_idlid' => 11],
            ['gesprekken_idgesprek' => $rejectedRequest, 'lid_idlid' => 1],
        ]);

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Coachgesprekken')
            ->assertSee('Ada Voorbeeld')
            ->assertSee('Ik wil mijn voortgang bespreken.')
            ->assertSee('Oud verzoek zonder datum')
            ->assertSee('Geen datum opgegeven')
            ->assertSee('Goedgekeurd')
            ->assertDontSee('Deze afgekeurde aanvraag mag niet in de lijst staan.')
            ->assertDontSee('Ik wil hulp met mijn leerdoel.')
            ->assertSee('13-11-2026 10:00')
            ->assertDontSee('<h3>Gespreksaanvragen</h3>', false);

        $this->put('/docent/gesprekaanvragen/'.$firstRequest.'/goedkeuren')
            ->assertRedirect('/dashboard?aanvragen=verwerkt#gesprekken');
        $this->assertDatabaseHas('gesprekken', ['idgesprek' => $firstRequest, 'afspraken' => 'approved']);
        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Goedgekeurd')
            ->assertSee('13-11-2026 10:00')
            ->assertSee('Gespreksaanvraag')
            ->assertSee('Volgende stap')
            ->assertDontSee('<h3>Gespreksaanvragen</h3>', false);
        $this->put('/docent/gesprekaanvragen/'.$secondRequest.'/afkeuren')->assertNotFound();

        $this->actingAs(User::findOrFail(2))
            ->put('/docent/gesprekaanvragen/'.$secondRequest.'/afkeuren')
            ->assertRedirect('/dashboard?aanvragen=verwerkt#gesprekken');
        $this->assertDatabaseHas('gesprekken', ['idgesprek' => $secondRequest, 'afspraken' => 'rejected']);

        $this->actingAs(User::findOrFail(11))
            ->put('/docent/gesprekaanvragen/'.$firstRequest.'/afkeuren')
            ->assertForbidden();
    }

    public function test_docent_sees_and_can_approve_requests_sent_directly_to_them_without_a_class_assignment(): void
    {
        Schema::drop('student_coach');
        Schema::drop('student');
        Schema::drop('coach');
        DB::statement('CREATE TABLE groep (idgroep INTEGER PRIMARY KEY, groepnaam TEXT, status TEXT)');
        DB::statement('CREATE TABLE groep_has_lid (idgroep INTEGER, idlid INTEGER)');
        DB::statement('CREATE TABLE gesprekken_has_lid (gesprekken_idgesprek INTEGER, lid_idlid INTEGER)');
        DB::table('groep')->insert(['idgroep' => 1, 'groepnaam' => 'Alle leden', 'status' => null]);
        DB::table('groep_has_lid')->insert([
            ['idgroep' => 1, 'idlid' => 1],
            ['idgroep' => 1, 'idlid' => 11],
        ]);
        $request = DB::table('gesprekken')->insertGetId([
            'datum' => '2026-11-13 10:00:00',
            'onderwerp' => DocentDashboard::MEETING_REQUEST_TOPIC,
            'inhoud' => 'Mijn aanvraag aan deze docent.',
            'afspraken' => 'pending',
        ]);
        DB::table('gesprekken_has_lid')->insert([
            ['gesprekken_idgesprek' => $request, 'lid_idlid' => 11],
            ['gesprekken_idgesprek' => $request, 'lid_idlid' => 1],
        ]);

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Ada Voorbeeld')
            ->assertSee('Mijn aanvraag aan deze docent.')
            ->assertSee('Aangevraagd');

        $this->put('/docent/gesprekaanvragen/'.$request.'/goedkeuren')
            ->assertRedirect('/dashboard?aanvragen=verwerkt#gesprekken');
        $this->assertDatabaseHas('gesprekken', ['idgesprek' => $request, 'afspraken' => 'approved']);
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

    public function test_docent_can_see_their_active_class_beside_the_student_list(): void
    {
        DB::statement('CREATE TABLE groep (idgroep INTEGER PRIMARY KEY, groepnaam TEXT, status TEXT)');
        DB::statement('CREATE TABLE groep_has_lid (idgroep INTEGER, idlid INTEGER)');
        DB::table('groep')->insert([
            ['idgroep' => 1, 'groepnaam' => 'Alle leden', 'status' => null],
            ['idgroep' => 2, 'groepnaam' => 'ICT 2A', 'status' => 'actief'],
            ['idgroep' => 3, 'groepnaam' => 'ICT 2B', 'status' => 'inactief'],
        ]);
        DB::table('groep_has_lid')->insert([
            ['idgroep' => 1, 'idlid' => 1],
            ['idgroep' => 2, 'idlid' => 1],
            ['idgroep' => 2, 'idlid' => 13],
            ['idgroep' => 3, 'idlid' => 1],
            ['idgroep' => 2, 'idlid' => 2],
        ]);

        $data = app(DocentDashboard::class)->data(User::findOrFail(1), Request::create('/dashboard'));

        $this->assertSame(['ICT 2A'], $data['teacherGroups']->pluck('groepnaam')->all());
        $this->assertSame([11, 12, 13], $data['students']->pluck('idstudent')->sort()->values()->all());
        $this->assertSame(3, $data['studentCount']);
        $this->assertSame(['ICT 2A', 'SD2A', 'SD2B'], $data['classes']->values()->all());
        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Jouw klas')
            ->assertSee('ICT 2A')
            ->assertSee('Privé Voorbeeld')
            ->assertDontSee('ICT 2B')
            ->assertDontSee('Alle leden');
    }

    public function test_teacher_group_roster_is_visible_without_student_profile_tables(): void
    {
        DB::statement('CREATE TABLE groep (idgroep INTEGER PRIMARY KEY, groepnaam TEXT, status TEXT)');
        DB::statement('CREATE TABLE groep_has_lid (idgroep INTEGER, idlid INTEGER)');
        DB::table('groep')->insert(['idgroep' => 1, 'groepnaam' => 'ICT 2A', 'status' => 'actief']);
        DB::table('groep_has_lid')->insert([
            ['idgroep' => 1, 'idlid' => 1],
            ['idgroep' => 1, 'idlid' => 11],
            ['idgroep' => 1, 'idlid' => 12],
        ]);
        Schema::drop('coach');
        Schema::drop('student');

        $data = app(DocentDashboard::class)->data(User::findOrFail(1), Request::create('/dashboard'));
        $this->assertSame(['Ada', 'Bram'], $data['visibleGroupStudents']->pluck('voornaam')->all());
        $this->assertSame(2, $data['studentCount']);

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('ICT 2A')
            ->assertSee('<h2>Mijn studenten <span class="count">2</span></h2>', false)
            ->assertSee('<strong>2</strong>', false)
            ->assertSee('Ada')
            ->assertSee('Bram');
    }

    public function test_dashboard_renders_escaped_records_and_student_detail(): void
    {
        $response = $this->actingAs(User::findOrFail(1))->get('/dashboard?student=11');
        $response->assertOk()->assertSee('Samenwerken')->assertSee('Volgende stap')->assertSee('Kennismaking')
            ->assertSee('Gesprek bewerken')
            ->assertDontSee("@include('dashboards.docent-meeting-edit')")
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Verborgen notitie')->assertDontSee('Andere auteur')->assertDontSee('Geheim gesprek');
        if (getenv('DOCENT_PREVIEW')) {
            file_put_contents(getenv('DOCENT_PREVIEW'), $response->getContent());
        }
    }

    public function test_missing_coach_profile_and_empty_results_have_honest_states(): void
    {
        DB::table('coach')->where('lid_idlid', 1)->delete();
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertOk()->assertSee('nog geen coachprofiel')->assertSee('Nog geen leerlingen gekoppeld');
    }

    public function test_missing_coach_table_does_not_break_the_dashboard_or_student_creation(): void
    {
        Schema::drop('coach');

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('nog geen coachprofiel')
            ->assertSee('Nog geen leerlingen gekoppeld');

        $this->post('/docent/studenten', [])->assertForbidden();
    }

    public function test_legacy_notes_and_meetings_work_for_students_in_the_docents_groups(): void
    {
        Schema::drop('student_coach');
        Schema::drop('student');
        Schema::drop('coach');
        Schema::drop('begeleidingsnotitie');
        Schema::drop('gesprekken');

        DB::statement('CREATE TABLE groep (idgroep INTEGER PRIMARY KEY, groepnaam TEXT, status TEXT)');
        DB::statement('CREATE TABLE groep_has_lid (idgroep INTEGER, idlid INTEGER)');
        DB::statement('CREATE TABLE begeleidingsnotitie (id_notitie INTEGER PRIMARY KEY AUTOINCREMENT, inhoud TEXT, datum TEXT, type TEXT, lid_idlid INTEGER)');
        DB::statement('CREATE TABLE gesprekken (idgesprek INTEGER PRIMARY KEY AUTOINCREMENT, datum TEXT, onderwerp TEXT, inhoud TEXT, afspraken TEXT)');
        DB::statement('CREATE TABLE gesprekken_has_lid (gesprekken_idgesprek INTEGER, lid_idlid INTEGER)');
        DB::statement('CREATE TABLE leeruitkomsten_has_lid (leeruitkomsten_id INTEGER, lid_idlid INTEGER, status TEXT)');
        DB::table('groep')->insert(['idgroep' => 1, 'groepnaam' => 'ICT 2A', 'status' => 'actief']);
        DB::table('groep_has_lid')->insert([
            ['idgroep' => 1, 'idlid' => 1],
            ['idgroep' => 1, 'idlid' => 11],
            ['idgroep' => 1, 'idlid' => 12],
        ]);
        DB::table('leeruitkomsten_has_lid')->insert([
            ['leeruitkomsten_id' => 1, 'lid_idlid' => 11, 'status' => 'Behaald'],
            ['leeruitkomsten_id' => 2, 'lid_idlid' => 11, 'status' => 'Bezig'],
        ]);
        DB::table('leeruitkomsten')->where('leeruitkomsten_id', 1)->update(['omschrijving' => 'Samenwerken met de klas.']);
        DB::table('begeleidingsnotitie')->insert([
            ['lid_idlid' => 11, 'inhoud' => 'Bestaande klasnotitie', 'datum' => '2026-11-03 10:00:00', 'type' => 'gesprek'],
            ['lid_idlid' => 0, 'inhoud' => 'Niet gekoppelde notitie', 'datum' => '2026-11-03 10:00:00', 'type' => 'gesprek'],
        ]);
        DB::table('gesprekken')->insert([
            'datum' => '2026-11-05 10:00:00',
            'onderwerp' => 'Niet gekoppeld oud gesprek',
        ]);

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Bestaande klasnotitie')
            ->assertDontSee('Niet gekoppelde notitie')
            ->assertDontSee('Niet gekoppeld oud gesprek')
            ->assertSee('+ Gesprek plannen')
            ->assertSee('+ Notitie toevoegen')
            ->assertSee('1/2 behaald')
            ->assertSee('aria-label="Bekijk Ada Voorbeeld"', false);

        $this->actingAs(User::findOrFail(1))->get('/dashboard?student=11')
            ->assertOk()
            ->assertSee('STUDENTDOSSIER')
            ->assertSee('Ada Voorbeeld')
            ->assertSee('ICT 2A')
            ->assertSee('1/2 leeruitkomsten behaald')
            ->assertSee('11@example.test')
            ->assertSee('Samenwerken')
            ->assertSee('Samenwerken met de klas.')
            ->assertSee('Programmeren')
            ->assertSee('Behaald')
            ->assertSee('Bezig')
            ->assertSee('--progress: 50%', false)
            ->assertDontSee('Studentnummer')
            ->assertDontSee('Opleiding')
            ->assertDontSee('Niet bekend')
            ->assertDontSee('Deze student is niet beschikbaar binnen jouw huidige begeleiding.');
        $this->get('/dashboard?student=13')
            ->assertOk()
            ->assertSee('Deze student is niet beschikbaar binnen jouw huidige begeleiding.')
            ->assertDontSee('Privé Voorbeeld');

        $this->post('/docent/notities', [
            'idstudent' => 11,
            'type' => 'Notitie',
            'inhoud' => 'Nieuwe legacy notitie',
        ])->assertRedirect('/dashboard#notities');
        $note = DB::table('begeleidingsnotitie')->where('inhoud', 'Nieuwe legacy notitie')->first();
        $this->assertSame(11, $note->lid_idlid);
        $this->put('/docent/notities/'.$note->id_notitie, ['type' => 'Afspraak', 'inhoud' => 'Aangepaste legacy notitie'])->assertRedirect();
        $this->assertDatabaseHas('begeleidingsnotitie', ['id_notitie' => $note->id_notitie, 'inhoud' => 'Aangepaste legacy notitie']);
        $this->delete('/docent/notities/'.$note->id_notitie)->assertRedirect();
        $this->assertDatabaseMissing('begeleidingsnotitie', ['id_notitie' => $note->id_notitie]);

        $meetingInput = [
            'student_idstudent' => 11,
            'onderwerp' => 'Legacy coachgesprek',
            'datum' => '2026-11-10T10:30',
            'inhoud' => 'Gespreksverslag',
            'afspraken' => 'Vervolgafspraak',
        ];
        $this->post('/docent/gesprekken', $meetingInput)->assertRedirect('/dashboard#gesprekken');
        $meetingId = DB::table('gesprekken')->where('onderwerp', 'Legacy coachgesprek')->value('idgesprek');
        $this->assertDatabaseHas('gesprekken_has_lid', ['gesprekken_idgesprek' => $meetingId, 'lid_idlid' => 1]);
        $this->assertDatabaseHas('gesprekken_has_lid', ['gesprekken_idgesprek' => $meetingId, 'lid_idlid' => 11]);
        $this->put('/docent/gesprekken/'.$meetingId, array_replace($meetingInput, ['onderwerp' => 'Verplaatst legacy gesprek']))->assertRedirect();
        $this->assertDatabaseHas('gesprekken', ['idgesprek' => $meetingId, 'onderwerp' => 'Verplaatst legacy gesprek']);
        $this->delete('/docent/gesprekken/'.$meetingId)->assertRedirect();
        $this->assertDatabaseMissing('gesprekken', ['idgesprek' => $meetingId]);
        $this->assertDatabaseMissing('gesprekken_has_lid', ['gesprekken_idgesprek' => $meetingId]);

        $this->post('/docent/notities', ['idstudent' => 13, 'type' => 'Notitie', 'inhoud' => 'Buiten de klas'])->assertForbidden();
    }

    public function test_student_sees_only_their_legacy_linked_coach_conversations(): void
    {
        DB::statement('CREATE TABLE leeruitkomsten_has_lid (leeruitkomsten_id INTEGER, lid_idlid INTEGER, status TEXT)');
        DB::statement('CREATE TABLE fase (idfase INTEGER PRIMARY KEY, naam TEXT, volgorde INTEGER)');
        DB::statement('ALTER TABLE leeruitkomsten ADD COLUMN fase_idfase INTEGER');
        DB::statement('CREATE TABLE urenregistratie (idurenregistratie INTEGER PRIMARY KEY AUTOINCREMENT, datum TEXT, omschrijving TEXT, uren REAL, status TEXT, aangemaakt_op TEXT, lid_id INTEGER)');
        DB::statement('CREATE TABLE reflectie (idreflectie INTEGER PRIMARY KEY AUTOINCREMENT, inhoud TEXT, datum TEXT, fase_idfase INTEGER, lid_idlid INTEGER)');
        Schema::drop('gesprekken');
        Schema::drop('begeleidingsnotitie');
        DB::statement('CREATE TABLE gesprekken (idgesprek INTEGER PRIMARY KEY AUTOINCREMENT, datum TEXT, onderwerp TEXT, inhoud TEXT, afspraken TEXT)');
        DB::statement('CREATE TABLE gesprekken_has_lid (gesprekken_idgesprek INTEGER, lid_idlid INTEGER)');
        DB::statement('CREATE TABLE begeleidingsnotitie (id_notitie INTEGER PRIMARY KEY AUTOINCREMENT, inhoud TEXT, datum TEXT, type TEXT, lid_idlid INTEGER)');

        DB::table('gesprekken')->insert([
            ['datum' => '2026-11-05 10:00:00', 'onderwerp' => 'Mijn coachgesprek', 'inhoud' => 'Mijn verslag', 'afspraken' => 'Mijn afspraak'],
            ['datum' => '2026-11-06 10:00:00', 'onderwerp' => 'Gesprek van een andere leerling', 'inhoud' => 'Privéverslag', 'afspraken' => 'Privéafspraak'],
        ]);
        DB::table('gesprekken_has_lid')->insert([
            ['gesprekken_idgesprek' => 1, 'lid_idlid' => 11],
            ['gesprekken_idgesprek' => 1, 'lid_idlid' => 1],
            ['gesprekken_idgesprek' => 2, 'lid_idlid' => 12],
            ['gesprekken_idgesprek' => 2, 'lid_idlid' => 1],
        ]);
        DB::table('begeleidingsnotitie')->insert([
            ['lid_idlid' => 11, 'inhoud' => 'Mijn notitie', 'datum' => '2026-11-04 10:00:00', 'type' => 'Voortgang'],
            ['lid_idlid' => 12, 'inhoud' => 'Notitie van een andere leerling', 'datum' => '2026-11-04 10:00:00', 'type' => 'Privé'],
        ]);

        DB::connection()->getPdo()->sqliteCreateFunction('FIELD', function ($value, ...$values) {
            $position = array_search($value, $values, true);

            return $position === false ? 0 : $position + 1;
        });
        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($date, $format) => \Illuminate\Support\Carbon::parse($date)->format(str_replace('%Y-%m', 'Y-m', $format)));

        $this->actingAs(User::findOrFail(11))->get('/dashboard')
            ->assertOk()
            ->assertSee('Mijn coachgesprek')
            ->assertSee('Mijn verslag')
            ->assertSee('Mijn afspraak')
            ->assertSee('Notities van je docent')
            ->assertSee('Mijn notitie')
            ->assertDontSee('Gesprek van een andere leerling')
            ->assertDontSee('Privéverslag')
            ->assertDontSee('Privéafspraak')
            ->assertDontSee('Notitie van een andere leerling');
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

    public function test_docent_can_manage_meetings_but_not_assessments_or_student_profiles(): void
    {
        $this->actingAs(User::findOrFail(1));
        $input = ['student_idstudent' => 11, 'onderwerp' => 'Evaluatie', 'datum' => '2026-11-10T10:30', 'inhoud' => 'Verslag', 'afspraken' => 'Vervolg'];
        $this->post('/docent/gesprekken', $input)->assertRedirect('/dashboard#gesprekken');
        $id = DB::table('gesprekken')->where('onderwerp', 'Evaluatie')->value('idgesprek');
        $this->put('/docent/gesprekken/'.$id, array_replace($input, ['onderwerp' => 'Verplaatst', 'datum' => '2026-11-11T12:00']))->assertRedirect();
        $this->assertDatabaseHas('gesprekken', ['idgesprek' => $id, 'onderwerp' => 'Verplaatst']);
        $this->put('/docent/leeruitkomsten/2', ['status' => 'Behaald', 'opmerking' => 'Goed gedaan'])->assertForbidden();
        $this->assertDatabaseHas('student_leeruitkomsten', ['id_leeruitkomsten' => 2, 'status' => 'Bezig']);
        $this->get('/dashboard?student=11')->assertOk()->assertSee('50%')->assertDontSee('Goed gedaan');
        $this->put('/docent/studenten/11', ['klas' => 'SD3A', 'opleiding' => 'ICT'])->assertForbidden();
        $this->assertDatabaseHas('student', ['idstudent' => 11, 'klas' => 'SD2A', 'opleiding' => 'Software Development']);
        $this->get('/dashboard?student=11')->assertOk()->assertSee('SD2A')->assertSee('Software Development')->assertDontSee('Studentgegevens bewerken');
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
        $this->put('/docent/leeruitkomsten/3', ['status' => 'Behaald'])->assertForbidden();
        $this->put('/docent/studenten/14', ['klas' => 'Nieuw'])->assertForbidden();
        DB::table('student_coach')->where('student_idstudent', 11)->update(['status' => 'inactief']);
        $this->delete('/docent/notities/1')->assertNotFound();
        $this->assertDatabaseHas('begeleidingsnotitie', ['id_notitie' => 3, 'inhoud' => 'Andere auteur']);
    }

    public function test_invalid_input_is_preserved_and_never_written(): void
    {
        $this->actingAs(User::findOrFail(1));
        $this->from('/dashboard#notities')->post('/docent/notities', ['_form' => 'note-new', 'idstudent' => 11, 'type' => 'Notitie', 'inhoud' => ''])->assertSessionHasErrors('inhoud')->assertSessionHasInput('_form', 'note-new');
        $this->put('/docent/leeruitkomsten/1', ['status' => 'Onbekend'])->assertForbidden();
        $this->post('/docent/gesprekken', ['student_idstudent' => 11, 'onderwerp' => 'Ongeldig', 'datum' => 'morgen'])->assertSessionHasErrors('datum');
        $this->assertDatabaseMissing('gesprekken', ['onderwerp' => 'Ongeldig']);
        $this->put('/docent/studenten/11', ['klas' => str_repeat('x', 21)])->assertForbidden();
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

    public function test_student_list_uses_compact_view_links(): void
    {
        $response = $this->actingAs(User::findOrFail(1))->get('/dashboard?student=11');
        $response->assertOk()->assertSee('Bekijk ↗')->assertDontSee('Student toevoegen')->assertDontSee('class="student-actions"', false)->assertDontSee('docent/studenten/11" data-confirm', false);
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

    public function test_only_admin_can_assign_and_edit_outcomes_without_resetting_existing_assessments(): void
    {
        $this->actingAs(User::findOrFail(1))->post('/docent/studenten/12/leeruitkomsten', ['leeruitkomsten_leeruitkomsten_id' => 1])->assertForbidden();
        $this->post('/docent/studenten/12/leeruitkomsten/nieuw', ['titel' => 'Verboden'])->assertForbidden();
        $this->post('/beheer/gebruikers/12/leeruitkomsten', ['outcome_id' => 1])->assertForbidden();
        $this->actingAs(User::findOrFail(11))->put('/beheer/gebruikers/11/leeruitkomsten/1', ['status' => 'Behaald'])->assertForbidden();
        $this->signInAsAdmin();
        $this->post('/beheer/gebruikers/12/leeruitkomsten', ['outcome_id' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->put('/beheer/gebruikers/12/leeruitkomsten/1', ['status' => 'Behaald', 'opmerking' => 'Afgerond'])->assertSessionHasNoErrors();
        $this->post('/beheer/gebruikers/12/leeruitkomsten', ['outcome_id' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('student_leeruitkomsten')->where('student_idstudent', 12)->count());
        $this->assertDatabaseHas('student_leeruitkomsten', ['student_idstudent' => 12, 'status' => 'Behaald', 'opmerking' => 'Afgerond']);
        $this->put('/beheer/gebruikers/12/leeruitkomsten/2', ['status' => 'Behaald'])->assertNotFound();
        $this->put('/beheer/gebruikers/12/leeruitkomsten/1', ['status' => 'Onbekend'])->assertSessionHasErrors('status');
        $this->post('/beheer/gebruikers/12/leeruitkomsten', ['outcome_id' => 999])->assertSessionHasErrors('outcome_id');
        $this->get('/beheer/gebruikers/12/bewerken')->assertOk()->assertSee('Beoordeling bewerken')->assertSee('Afgerond');
        $this->actingAs(User::findOrFail(1))->get('/dashboard?student=12')->assertOk()->assertSee('Afgerond')->assertDontSee('Beoordeling opslaan');
    }

    public function test_meeting_datetime_with_seconds_is_saved_in_database_format(): void
    {
        $this->actingAs(User::findOrFail(1))->post('/docent/gesprekken', ['student_idstudent' => 11, 'onderwerp' => 'Met seconden', 'datum' => '2026-11-10T10:30:00'])->assertRedirect();
        $this->assertDatabaseHas('gesprekken', ['onderwerp' => 'Met seconden', 'datum' => '2026-11-10 10:30:00']);
    }

    private function signInAsAdmin(): void
    {
        $this->withoutVite();
        DB::table('rol')->insert(['idrol' => 3, 'naam' => 'Admin']);
        DB::table('lid')->insert(['idlid' => 3, 'voornaam' => 'Beheerder', 'email' => 'beheer@example.test', 'status' => 'actief', 'rol_idrol' => 3]);
        $this->actingAs(User::findOrFail(3));
    }

    public function test_admin_updates_student_profile_and_docent_sees_changes_read_only(): void
    {
        $this->signInAsAdmin();
        $this->get('/beheer/gebruikers/11/bewerken')->assertOk()->assertSee('name="klas"', false)->assertSee('SD2A');
        $input = ['voornaam' => 'Ada', 'achternaam' => 'Voorbeeld', 'email' => '11@example.test', 'rol_idrol' => 2, 'status' => 'actief', 'klas' => 'SD3A', 'opleiding' => 'ICT', 'studentnummer' => 'S11'];
        $this->put('/beheer/gebruikers/11', $input)->assertRedirect('/dashboard#gebruikers');
        $this->assertDatabaseHas('student', ['idstudent' => 11, 'klas' => 'SD3A', 'opleiding' => 'ICT']);
        $this->actingAs(User::findOrFail(1))->get('/dashboard?student=11')->assertOk()->assertSee('SD3A')->assertSee('ICT')->assertDontSee('Studentgegevens bewerken');
        foreach ([1, 11] as $userId) {
            $this->actingAs(User::findOrFail($userId))->put('/beheer/gebruikers/11', array_replace($input, ['klas' => 'Verboden']))->assertForbidden();
        }
        $this->assertDatabaseHas('student', ['idstudent' => 11, 'klas' => 'SD3A']);
        $this->actingAs(User::findOrFail(3))->put('/beheer/gebruikers/11', array_replace($input, ['klas' => '', 'opleiding' => '']))->assertRedirect();
        $this->assertDatabaseHas('student', ['idstudent' => 11, 'klas' => null, 'opleiding' => null]);
    }

    public function test_invalid_student_profile_does_not_change_account_or_profile(): void
    {
        $this->signInAsAdmin();
        $this->put('/beheer/gebruikers/11', [
            'voornaam' => 'Gewijzigd', 'achternaam' => 'Voorbeeld', 'email' => '11@example.test',
            'rol_idrol' => 2, 'status' => 'actief', 'klas' => str_repeat('x', 21),
            'opleiding' => str_repeat('x', 51), 'studentnummer' => 'S12',
        ])->assertSessionHasErrors(['klas', 'opleiding', 'studentnummer']);
        $this->assertDatabaseHas('lid', ['idlid' => 11, 'voornaam' => 'Ada']);
        $this->assertDatabaseHas('student', ['idstudent' => 11, 'klas' => 'SD2A', 'studentnummer' => 'S11']);
    }

    public function test_admin_creates_student_profile_and_preserves_omitted_fields_on_update(): void
    {
        $this->signInAsAdmin();
        $input = $this->newStudentInput(['rol_idrol' => 2, 'status' => 'actief', 'studentnummer' => 'S99']);
        $this->post('/beheer/gebruikers', $input)->assertRedirect('/dashboard');
        $member = User::where('email', 'nieuw@example.test')->firstOrFail();
        $this->assertDatabaseHas('student', ['lid_idlid' => $member->getKey(), 'klas' => 'SD1', 'opleiding' => 'ICT', 'studentnummer' => 'S99']);
        unset($input['klas'], $input['opleiding'], $input['studentnummer']);
        $this->put('/beheer/gebruikers/'.$member->getKey(), $input)->assertRedirect();
        $this->assertDatabaseHas('student', ['lid_idlid' => $member->getKey(), 'klas' => 'SD1', 'opleiding' => 'ICT', 'studentnummer' => 'S99']);
        $this->assertDatabaseCount('student', 6);
    }

    public function test_student_detail_is_read_only_and_shows_saved_information(): void
    {
        DB::table('student_leeruitkomsten')->where('id_leeruitkomsten', 1)->update(['opmerking' => 'Zelfstandig afgerond']);
        $response = $this->actingAs(User::findOrFail(1))->get('/dashboard?student=11');
        $response->assertOk()->assertSee('Zelfstandig afgerond')->assertSee('S11')->assertSee('SD2A');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(0, $xpath->query('//*[@id="student-detail"]//form | //*[@id="student-detail"]//input | //*[@id="student-detail"]//select | //*[@id="student-detail"]//textarea')->length);
        $this->assertSame(4, $xpath->query('//*[@id="student-detail"]//dl/div')->length);
        $this->assertSame(1, $xpath->query('//*[@id="student-detail"]//div[@class="dossier-outcomes"][@role="region"][@tabindex="0"]')->length);
        $response->assertSee('max-height:min(55vh,360px)', false)->assertSee('overflow-y:auto', false);
        $response->assertDontSee('Nieuwe leeruitkomst maken')->assertDontSee('Voortgang bewerken');
    }

    private function newStudentInput(array $overrides = []): array
    {
        return array_replace(['voornaam' => 'Nieuwe', 'achternaam' => 'Student', 'email' => 'nieuw@example.test', 'password' => 'veilig-test-wachtwoord', 'password_confirmation' => 'veilig-test-wachtwoord', 'klas' => 'SD1', 'opleiding' => 'ICT'], $overrides);
    }

    public function test_admin_creates_missing_teacher_profile_without_duplicating_existing_profiles(): void
    {
        $this->signInAsAdmin();
        DB::table('rol')->where('idrol', 1)->update(['naam' => 'Coach']);
        DB::table('coach')->where('lid_idlid', 1)->delete();
        $this->get('/beheer/gebruikers/1/bewerken')->assertOk()->assertSee('Coach')->assertDontSee('Docent (Coach)');
        $input = ['voornaam' => 'Docent', 'achternaam' => 'Voorbeeld', 'email' => '1@example.test', 'rol_idrol' => 1, 'status' => 'actief'];
        $this->put('/beheer/gebruikers/1', $input)->assertRedirect();
        $profileId = DB::table('coach')->where('lid_idlid', 1)->value('idcoach');
        $this->assertNotNull($profileId);
        $this->put('/beheer/gebruikers/1', $input)->assertRedirect();
        $this->assertSame(1, DB::table('coach')->where('lid_idlid', 1)->count());
        $this->assertEquals($profileId, DB::table('coach')->where('lid_idlid', 1)->value('idcoach'));
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertOk()->assertDontSee('Je account heeft nog geen coachprofiel');
        foreach (['Coach', 'Docent'] as $name) {
            $role = new Role;
            $role->naam = $name;
            $this->assertSame($name, $role->displayName());
        }
    }

    public function test_docent_cannot_create_student_account_profile_or_assignment(): void
    {
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertOk()->assertDontSee('Student toevoegen');
        $response = $this->post('/docent/studenten', $this->newStudentInput(['rol_idrol' => 1]));
        $response->assertForbidden();
        $this->assertDatabaseMissing('lid', ['email' => 'nieuw@example.test']);
        $this->assertDatabaseCount('student', 5);
        $this->assertDatabaseCount('student_coach', 5);
    }

    public function test_invalid_student_creation_writes_nothing(): void
    {
        $this->actingAs(User::findOrFail(1));
        $this->post('/docent/studenten', $this->newStudentInput(['email' => '11@example.test', 'password_confirmation' => 'anders']))->assertForbidden();
        $this->assertDatabaseCount('lid', 7);
        $this->assertDatabaseCount('student', 5);
        $this->assertDatabaseCount('student_coach', 5);
    }

    public function test_students_and_docents_without_profiles_cannot_create_students(): void
    {
        $this->actingAs(User::findOrFail(11))->post('/docent/studenten', $this->newStudentInput())->assertForbidden();
        DB::table('coach')->where('lid_idlid', 1)->delete();
        $this->actingAs(User::findOrFail(1))->post('/docent/studenten', $this->newStudentInput())->assertForbidden();
        $this->assertDatabaseMissing('lid', ['email' => 'nieuw@example.test']);
    }

    public function test_admin_assessments_update_the_teachers_chart(): void
    {
        DB::table('student_leeruitkomsten')->delete();
        DB::table('leeruitkomsten')->delete();
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertOk()->assertSee('Nog geen data');
        $this->signInAsAdmin();
        $this->post('/beheer/gebruikers/11/leeruitkomsten', ['titel' => 'Zelfstandig programmeren'])->assertSessionHasNoErrors();
        $id = DB::table('leeruitkomsten')->value('leeruitkomsten_id');
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertOk()->assertSee('--progress: 0%', false);
        $this->actingAs(User::findOrFail(3))->put('/beheer/gebruikers/11/leeruitkomsten/'.$id, ['status' => 'Behaald'])->assertSessionHasNoErrors();
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertOk()->assertSee('--progress: 100%', false);
        $this->actingAs(User::findOrFail(3))->post('/beheer/gebruikers/12/leeruitkomsten', ['titel' => 'Samenwerken'])->assertSessionHasNoErrors();
        $this->actingAs(User::findOrFail(1))->get('/dashboard')->assertOk()->assertSee('--progress: 50%', false);
        $this->actingAs(User::findOrFail(3))->post('/beheer/gebruikers/11/leeruitkomsten', ['titel' => ''])->assertSessionHasErrors('titel');
        $this->assertDatabaseCount('leeruitkomsten', 2);
    }
}
