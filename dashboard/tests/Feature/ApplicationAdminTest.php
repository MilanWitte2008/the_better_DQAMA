<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApplicationAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Schema::create('rol', function (Blueprint $table) {
            $table->increments('idrol');
            $table->string('naam', 50)->nullable();
            $table->text('beschrijving')->nullable();
        });
        Schema::create('lid', function (Blueprint $table) {
            $table->increments('idlid');
            $table->string('voornaam', 50)->nullable();
            $table->string('achternaam', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('wachtwoord')->nullable();
            $table->string('status', 50)->nullable();
            $table->dateTime('aangemaakt_datum')->nullable();
            $table->unsignedInteger('rol_idrol')->nullable();
            $table->foreign('rol_idrol')->references('idrol')->on('rol');
        });
        DB::table('rol')->insert([
            ['idrol' => 101, 'naam' => 'Student', 'beschrijving' => 'Volgt een opleiding'],
            ['idrol' => 103, 'naam' => 'Admin', 'beschrijving' => 'Beheert de applicatie'],
        ]);
    }

    private function account(int $role = 103, string $status = 'actief'): User
    {
        $user = new User;
        $user->forceFill([
            'voornaam' => 'Test', 'achternaam' => 'Beheerder',
            'email' => uniqid().'@example.com', 'wachtwoord' => Hash::make('veilig-wachtwoord'),
            'status' => $status, 'rol_idrol' => $role,
            'aangemaakt_datum' => now(),
        ])->save();

        return $user;
    }

    private function input(array $overrides = []): array
    {
        return array_replace([
            'voornaam' => 'Nieuwe', 'achternaam' => 'Gebruiker', 'email' => 'nieuw@example.com',
            'rol_idrol' => 101, 'status' => 'actief',
            'password' => 'een-veilig-wachtwoord', 'password_confirmation' => 'een-veilig-wachtwoord',
        ], $overrides);
    }

    public function test_admin_sees_database_records_and_can_filter(): void
    {
        $admin = $this->account();
        $this->actingAs($admin)->get('/beheer/dashboard')->assertOk()->assertSee($admin->email);
        $this->get('/beheer/dashboard?q=Test%20Beheerder')->assertOk()->assertSee($admin->email);
        $this->get('/beheer/dashboard?q=geen-resultaat')->assertOk()->assertSee('Geen gebruikers gevonden')->assertDontSee($admin->email);
        $this->get('/beheer/dashboard?role=101')->assertOk()->assertSee('Geen gebruikers gevonden');
        $this->get('/beheer/dashboard?status=onbekend')->assertSessionHasErrors('status');
    }

    public function test_admin_can_create_and_edit_an_account_without_exposing_password(): void
    {
        $this->actingAs($this->account())->get('/beheer/gebruikers/nieuw')->assertOk();
        $this->post('/beheer/gebruikers', $this->input())->assertRedirect('/dashboard');
        $member = User::where('email', 'nieuw@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('een-veilig-wachtwoord', $member->wachtwoord));
        $this->assertNotNull($member->aangemaakt_datum);
        $this->get('/beheer/gebruikers/'.$member->idlid.'/bewerken')->assertOk()->assertDontSee($member->wachtwoord);
        $hash = $member->wachtwoord;
        $this->put('/beheer/gebruikers/'.$member->idlid, $this->input(['voornaam' => 'Gewijzigd', 'status' => 'inactief', 'password' => '', 'password_confirmation' => '']))->assertRedirect('/dashboard#gebruikers');
        $this->assertSame('Gewijzigd', $member->fresh()->voornaam);
        $this->assertSame($hash, $member->fresh()->wachtwoord);
        $this->assertSame('inactief', $member->fresh()->status);
    }

    public function test_invalid_foreign_keys_duplicate_email_and_password_are_rejected(): void
    {
        $admin = $this->account();
        $this->actingAs($admin)->post('/beheer/gebruikers', $this->input([
            'email' => $admin->email, 'rol_idrol' => 999, 'password_confirmation' => 'anders',
        ]))->assertSessionHasErrors(['email', 'rol_idrol', 'password']);
        $this->assertSame(1, User::count());
    }

    public function test_admin_cannot_remove_own_access(): void
    {
        $admin = $this->account();
        $this->actingAs($admin)->put('/beheer/gebruikers/'.$admin->idlid, $this->input(['email' => $admin->email]))->assertSessionHasErrors('status');
        $this->put('/beheer/gebruikers/'.$admin->idlid, $this->input(['email' => $admin->email, 'rol_idrol' => 103, 'status' => 'inactief']))->assertSessionHasErrors('status');
        $this->assertSame('actief', $admin->fresh()->status);
        $this->assertSame(103, $admin->fresh()->rol_idrol);
    }

    public function test_other_roles_and_inactive_admins_cannot_manage_accounts(): void
    {
        $student = $this->account(101);
        $member = $this->account();
        $this->actingAs($student)->get('/beheer/gebruikers/nieuw')->assertForbidden();
        $this->get('/beheer/gebruikers/'.$member->idlid.'/bewerken')->assertForbidden();
        $this->post('/beheer/gebruikers', $this->input())->assertForbidden();
        $this->put('/beheer/gebruikers/'.$member->idlid, $this->input())->assertForbidden();
        $this->actingAs($this->account(103, 'inactief'))->followingRedirects()->get('/dashboard')->assertForbidden();
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = $this->account(101, 'inactief');
        $this->post('/login', ['email' => $user->email, 'password' => 'veilig-wachtwoord'])->assertSessionHas('login_error');
        $this->assertGuest();
    }

    public function test_analytics_count_selected_year_months_and_include_accounts_without_roles(): void
    {
        $admin = $this->account();
        $admin->forceFill(['aangemaakt_datum' => '2019-12-31 23:59:59'])->save();
        $this->account(101)->forceFill(['aangemaakt_datum' => '2020-01-01 00:00:00'])->save();
        $this->account(101, 'inactief')->forceFill(['aangemaakt_datum' => '2020-12-31 23:59:59'])->save();
        $this->account(101)->forceFill(['aangemaakt_datum' => '2021-01-01 00:00:00'])->save();
        $this->account(101)->forceFill(['aangemaakt_datum' => null, 'rol_idrol' => null])->save();

        $response = $this->actingAs($admin)->get('/beheer/dashboard?year=2020&q=geen-resultaat');

        $response->assertOk()->assertSee('Geen gebruikers gevonden');
        $analytics = $response->viewData('analytics');
        $this->assertSame(2020, $analytics['year']);
        $this->assertSame(2, $analytics['yearTotal']);
        $this->assertSame([1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1], $analytics['monthly']);
        $this->assertSame(80, $analytics['activePercent']);
        $this->assertSame([
            ['label' => 'Admin', 'value' => 1],
            ['label' => 'Student', 'value' => 3],
            ['label' => 'Geen rol', 'value' => 1],
        ], $analytics['roles']);
    }

    public function test_analytics_show_empty_months_and_reject_an_invalid_year(): void
    {
        $admin = $this->account();
        $admin->forceFill(['aangemaakt_datum' => '2019-01-01 00:00:00'])->save();

        $response = $this->actingAs($admin)->get('/beheer/dashboard?year=2020');

        $response->assertOk();
        $this->assertSame(0, $response->viewData('analytics')['yearTotal']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $response->viewData('analytics')['monthly']);
        $this->get('/beheer/dashboard?year=not-a-year')->assertSessionHasErrors('year');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/beheer/gebruikers/nieuw')->assertRedirect('/');
        $this->post('/beheer/gebruikers', $this->input())->assertRedirect('/');
    }
}
