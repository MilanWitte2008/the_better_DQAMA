<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('rol', function (Blueprint $table): void {
            $table->id('idrol');
            $table->string('naam');
        });
        Schema::create('lid', function (Blueprint $table): void {
            $table->id('idlid');
            $table->string('voornaam');
            $table->string('achternaam');
            $table->string('email');
            $table->string('wachtwoord');
            $table->string('status');
            $table->unsignedBigInteger('rol_idrol');
        });
        Schema::create('coach', function (Blueprint $table): void {
            $table->id('idcoach');
            $table->unsignedBigInteger('lid_idlid');
        });
        Schema::create('student', function (Blueprint $table): void {
            $table->id('idstudent');
            $table->unsignedBigInteger('lid_idlid');
        });
        Schema::create('student_coach', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_idstudent');
            $table->unsignedBigInteger('coach_idcoach');
            $table->string('status');
            $table->date('startdatum')->nullable();
            $table->date('einddatum')->nullable();
        });
        Schema::create('groep', function (Blueprint $table): void {
            $table->id('idgroep');
            $table->string('groepnaam');
            $table->string('status')->nullable();
        });
        Schema::create('groep_has_lid', function (Blueprint $table): void {
            $table->unsignedBigInteger('idgroep');
            $table->unsignedBigInteger('idlid');
        });
        Schema::create('doelen', function (Blueprint $table): void {
            $table->id('doel_id');
            $table->string('titel');
            $table->unsignedBigInteger('lid_idlid');
        });
        Schema::create('reflectie', function (Blueprint $table): void {
            $table->id('idreflectie');
            $table->text('inhoud');
            $table->date('datum');
            $table->unsignedBigInteger('lid_idlid');
        });
        Schema::create('leeruitkomsten', function (Blueprint $table): void {
            $table->id('leeruitkomsten_id');
            $table->string('titel');
            $table->string('code');
            $table->text('omschrijving')->nullable();
        });
        Schema::create('student_leeruitkomsten', function (Blueprint $table): void {
            $table->id('id_leeruitkomsten');
            $table->unsignedBigInteger('student_idstudent');
            $table->unsignedBigInteger('leeruitkomsten_leeruitkomsten_id');
            $table->string('status')->nullable();
            $table->unsignedInteger('vooruitgang')->nullable();
            $table->text('opmerking')->nullable();
            $table->timestamp('laatste_update')->nullable();
        });
        Schema::create('urenregistratie', function (Blueprint $table): void {
            $table->id('idurenregistratie');
            $table->date('datum');
            $table->string('omschrijving');
            $table->decimal('uren', 5, 1);
            $table->string('status');
            $table->unsignedBigInteger('lid_id');
        });
        Schema::create('gesprekken', function (Blueprint $table): void {
            $table->id('idgesprek');
            $table->unsignedBigInteger('student_idstudent')->nullable();
            $table->dateTime('datum')->nullable();
            $table->string('onderwerp')->nullable();
            $table->text('inhoud')->nullable();
            $table->text('afspraken')->nullable();
        });
        Schema::create('gesprekken_has_lid', function (Blueprint $table): void {
            $table->unsignedBigInteger('gesprekken_idgesprek');
            $table->unsignedBigInteger('lid_idlid');
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        DB::table('rol')->insert([
            ['idrol' => 1, 'naam' => 'Student'],
            ['idrol' => 2, 'naam' => 'Coach'],
            ['idrol' => 3, 'naam' => 'Docent'],
        ]);
        DB::table('lid')->insert([
            ['idlid' => 1, 'voornaam' => 'Sam', 'achternaam' => 'Student', 'email' => 'sam@example.test', 'wachtwoord' => 'secret', 'status' => 'actief', 'rol_idrol' => 1],
            ['idlid' => 2, 'voornaam' => 'Kim', 'achternaam' => 'Coach', 'email' => 'kim@example.test', 'wachtwoord' => 'secret', 'status' => 'actief', 'rol_idrol' => 2],
            ['idlid' => 3, 'voornaam' => 'Alex', 'achternaam' => 'Anders', 'email' => 'alex@example.test', 'wachtwoord' => 'secret', 'status' => 'actief', 'rol_idrol' => 2],
            ['idlid' => 4, 'voornaam' => 'Noor', 'achternaam' => 'Student', 'email' => 'noor@example.test', 'wachtwoord' => 'secret', 'status' => 'actief', 'rol_idrol' => 1],
            ['idlid' => 5, 'voornaam' => 'Rae', 'achternaam' => 'Coach', 'email' => 'rae@example.test', 'wachtwoord' => 'secret', 'status' => 'actief', 'rol_idrol' => 2],
            ['idlid' => 6, 'voornaam' => 'Jo', 'achternaam' => 'Docent', 'email' => 'jo@example.test', 'wachtwoord' => 'secret', 'status' => 'actief', 'rol_idrol' => 3],
        ]);
        DB::table('coach')->insert([
            ['idcoach' => 1, 'lid_idlid' => 2],
            ['idcoach' => 2, 'lid_idlid' => 3],
            ['idcoach' => 3, 'lid_idlid' => 5],
        ]);
        DB::table('student')->insert([
            ['idstudent' => 1, 'lid_idlid' => 1],
            ['idstudent' => 4, 'lid_idlid' => 4],
        ]);
        DB::table('student_coach')->insert([
            ['student_idstudent' => 1, 'coach_idcoach' => 1, 'status' => 'actief', 'startdatum' => null, 'einddatum' => null],
            ['student_idstudent' => 1, 'coach_idcoach' => 2, 'status' => 'inactief', 'startdatum' => null, 'einddatum' => null],
            ['student_idstudent' => 1, 'coach_idcoach' => 3, 'status' => 'actief', 'startdatum' => null, 'einddatum' => null],
        ]);
        DB::table('groep')->insert(['idgroep' => 1, 'groepnaam' => 'ICT 2A', 'status' => null]);
        DB::table('groep_has_lid')->insert([
            ['idgroep' => 1, 'idlid' => 1],
            ['idgroep' => 1, 'idlid' => 2],
        ]);

        DB::connection()->getPdo()->sqliteCreateFunction(
            'DATE_FORMAT',
            fn ($date, $format) => \Illuminate\Support\Carbon::parse($date)->format(str_replace('%Y-%m', 'Y-m', $format)),
        );
    }

    public function test_students_can_edit_and_delete_only_their_own_goals_and_reflections(): void
    {
        DB::table('doelen')->insert([
            ['doel_id' => 1, 'titel' => 'Oud doel', 'lid_idlid' => 1],
            ['doel_id' => 2, 'titel' => 'Anders doel', 'lid_idlid' => 4],
        ]);
        DB::table('reflectie')->insert([
            ['idreflectie' => 1, 'inhoud' => 'Oude reflectie', 'datum' => '2026-11-01', 'lid_idlid' => 1],
            ['idreflectie' => 2, 'inhoud' => 'Anders reflectie', 'datum' => '2026-11-01', 'lid_idlid' => 4],
        ]);

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('<div class="content-grid single-column">', false)
            ->assertSee('<details class="student-record-actions">', false)
            ->assertSee('Aanpassen of verwijderen');

        $this->actingAs(User::findOrFail(1))
            ->put('/dashboard/leerdoelen/1', ['titel' => 'Aangepast doel'])
            ->assertRedirect('/dashboard#leerdoelen');
        $this->assertDatabaseHas('doelen', ['doel_id' => 1, 'titel' => 'Aangepast doel']);
        $this->put('/dashboard/reflecties/1', ['inhoud' => 'Aangepaste reflectie'])
            ->assertRedirect('/dashboard#reflecties');
        $this->assertDatabaseHas('reflectie', ['idreflectie' => 1, 'inhoud' => 'Aangepaste reflectie']);
        $this->delete('/dashboard/leerdoelen/1')->assertRedirect('/dashboard#leerdoelen');
        $this->delete('/dashboard/reflecties/1')->assertRedirect('/dashboard#reflecties');
        $this->assertDatabaseMissing('doelen', ['doel_id' => 1]);
        $this->assertDatabaseMissing('reflectie', ['idreflectie' => 1]);
        $this->put('/dashboard/leerdoelen/2', ['titel' => 'Overnemen'])->assertNotFound();
        $this->delete('/dashboard/reflecties/2')->assertNotFound();
        $this->actingAs(User::findOrFail(2))->delete('/dashboard/leerdoelen/2')->assertForbidden();
        $this->assertDatabaseHas('doelen', ['doel_id' => 2, 'titel' => 'Anders doel']);
        $this->assertDatabaseHas('reflectie', ['idreflectie' => 2, 'inhoud' => 'Anders reflectie']);
    }

    public function test_students_can_read_the_results_written_by_their_coach(): void
    {
        DB::table('leeruitkomsten')->insert([
            'leeruitkomsten_id' => 1,
            'titel' => 'Samenwerken',
            'code' => 'LO1',
            'omschrijving' => 'Werkt goed samen.',
        ]);
        DB::table('student_leeruitkomsten')->insert([
            'student_idstudent' => 1,
            'leeruitkomsten_leeruitkomsten_id' => 1,
            'status' => 'Behaald',
            'vooruitgang' => 100,
            'opmerking' => 'Goed gedaan.',
            'laatste_update' => now(),
        ]);

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Mijn resultaten')
            ->assertSee('Samenwerken')
            ->assertSee('Behaald')
            ->assertSee('Goed gedaan.');
    }

    public function test_group_teachers_are_available_when_no_direct_coach_assignment_exists(): void
    {
        Schema::drop('student_coach');
        Schema::drop('coach');

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Kim Coach')
            ->assertDontSee('Jo Docent')
            ->assertDontSee('Rae Coach');

        $this->post('/dashboard/gesprekaanvraag', [
            'docent_id' => 2,
            'datum' => now()->addDay()->format('Y-m-d\TH:i'),
            'bericht' => 'Gesprek via mijn klas.',
        ])->assertRedirect('/dashboard#gesprekken');

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('gesprekken', [
            'onderwerp' => \App\DocentDashboard::MEETING_REQUEST_TOPIC,
            'inhoud' => 'Gesprek via mijn klas.',
            'afspraken' => 'pending',
        ]);
        $requestId = DB::table('gesprekken')->where('inhoud', 'Gesprek via mijn klas.')->value('idgesprek');
        $this->assertDatabaseHas('gesprekken_has_lid', ['gesprekken_idgesprek' => $requestId, 'lid_idlid' => 1]);
        $this->assertDatabaseHas('gesprekken_has_lid', ['gesprekken_idgesprek' => $requestId, 'lid_idlid' => 2]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => 2]);
    }

    public function test_group_teachers_are_available_from_the_all_members_group(): void
    {
        Schema::drop('student_coach');
        Schema::drop('coach');
        Schema::drop('student');
        DB::table('groep')->where('idgroep', 1)->update(['groepnaam' => 'Alle leden']);

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Kim Coach')
            ->assertDontSee('Jo Docent');

        $this->post('/dashboard/gesprekaanvraag', [
            'docent_id' => 2,
            'datum' => now()->addDay()->format('Y-m-d\TH:i'),
            'bericht' => 'Gesprek via de groep Alle leden.',
        ])->assertRedirect('/dashboard#gesprekken');

        $this->assertDatabaseHas('notifications', ['notifiable_id' => 2]);
        $requestId = DB::table('gesprekken')->where('inhoud', 'Gesprek via de groep Alle leden.')->value('idgesprek');
        $this->assertDatabaseHas('gesprekken_has_lid', ['gesprekken_idgesprek' => $requestId, 'lid_idlid' => 2]);
    }

    public function test_student_meeting_request_notifies_only_the_linked_active_coach(): void
    {
        $this->assertSame('Europe/Amsterdam', config('app.timezone'));
        DB::table('groep')->where('idgroep', 1)->update(['status' => 'actief']);
        DB::table('gesprekken')->insert([
            'student_idstudent' => 1,
            'datum' => '2026-11-12 10:00:00',
            'onderwerp' => 'Voortgang bespreken',
            'inhoud' => 'Bestaand gesprek',
            'afspraken' => 'Volgende week',
        ]);

        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Kies een docent')
            ->assertSee('Voortgang bespreken')
            ->assertSee('Bestaand gesprek')
            ->assertSee('<details class="meeting-request"', false)
            ->assertSee('<summary class="primary-button meeting-request-toggle">Gesprek aanvragen</summary>', false)
            ->assertSee('name="datum"', false)
            ->assertSee('type="datetime-local"', false)
            ->assertSee('lang="nl-NL" step="60"', false)
            ->assertSee('Kim Coach')
            ->assertSee('Rae Coach')
            ->assertDontSee('Jo Docent')
            ->assertDontSee('Alex Anders');

        $this->post('/dashboard/gesprekaanvraag', ['docent_id' => 2])
            ->assertSessionHasErrors('datum');
        $this->post('/dashboard/gesprekaanvraag', [
            'datum' => now()->addDay()->format('Y-m-d\TH:i'),
            'bericht' => 'Ik wil graag praten over mijn voortgang.',
        ])
            ->assertSessionHasErrors('docent_id');
        $this->post('/dashboard/gesprekaanvraag', ['docent_id' => 2])
            ->assertSessionHasErrors('datum');
        $this->post('/dashboard/gesprekaanvraag', [
            'docent_id' => 3, 'datum' => '2026-11-13T10:00', 'bericht' => 'Ongekoppelde docent',
        ])
            ->assertSessionHasErrors('docent_id');

        $this->post('/dashboard/gesprekaanvraag', [
            'docent_id' => 2,
            'datum' => '2026-11-13T10:00',
            'bericht' => 'Ik wil graag praten over mijn voortgang.',
        ])
            ->assertRedirect('/dashboard#gesprekken')
            ->assertSessionHas('success', 'Je gespreksaanvraag is naar je coach verstuurd.');

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('gesprekken', [
            'onderwerp' => \App\DocentDashboard::MEETING_REQUEST_TOPIC,
            'inhoud' => 'Ik wil graag praten over mijn voortgang.',
            'datum' => '2026-11-13 10:00:00',
            'afspraken' => 'pending',
        ]);
        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Gespreksaanvraag:')
            ->assertSee('In afwachting van de docent');
        $notification = DB::table('notifications')->where('notifiable_id', 2)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Ik wil graag praten over mijn voortgang.', $notification->data);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => 3]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => 5]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => 6]);

        $this->actingAs(User::findOrFail(3))->post('/dashboard/meldingen/'.$notification->id.'/lezen')->assertNotFound();
        $this->actingAs(User::findOrFail(5))->post('/dashboard/meldingen/'.$notification->id.'/lezen')->assertNotFound();
        $this->actingAs(User::findOrFail(6))->post('/dashboard/meldingen/'.$notification->id.'/lezen')->assertNotFound();
        $this->actingAs(User::findOrFail(2))->post('/dashboard/meldingen/'.$notification->id.'/lezen')
            ->assertRedirect('/dashboard#gesprekken');
        $this->assertNotNull(DB::table('notifications')->where('id', $notification->id)->value('read_at'));

        $requestId = DB::table('gesprekken')
            ->where('onderwerp', \App\DocentDashboard::MEETING_REQUEST_TOPIC)
            ->value('idgesprek');
        $this->assertNotNull($requestId);
        $this->assertTrue(app(\App\DocentDashboard::class)->studentMemberIds(User::findOrFail(2))->contains(1));
        $this->actingAs(User::findOrFail(2))
            ->put('/docent/gesprekaanvragen/'.$requestId.'/goedkeuren')
            ->assertRedirect('/dashboard?aanvragen=verwerkt#gesprekken');
        $this->actingAs(User::findOrFail(1))->get('/dashboard')
            ->assertOk()
            ->assertSee('Goedgekeurd')
            ->assertSee('13-11-2026 10:00');
    }
}
