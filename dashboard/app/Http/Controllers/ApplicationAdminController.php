<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApplicationAdminController extends Controller
{
    // Alleen een ingelogde, actieve beheerder mag deze acties uitvoeren.
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->dashboardRoleKey() === 'beheerder' && strtolower((string) $request->user()->status) === 'actief', 403);
    }

    // Haal de gegevens op die we op het dashboard willen tonen.
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        // Controleer de zoektekst en de gekozen filters.
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,'.now()->year],
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'integer', 'exists:rol,idrol'],
            'status' => ['nullable', Rule::in(['actief', 'inactief'])],
        ]);
        // Zoek gebruikers met hun rol. Toon 12 gebruikers per pagina.
        $members = User::query()->with('rol')
            ->when($filters['q'] ?? null, function ($query, $search) {
                foreach (preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $term) {
                    $query->where(function ($query) use ($term) {
                        foreach (['lid.voornaam', 'lid.achternaam', 'lid.email'] as $column) {
                            $query->orWhere($column, 'like', '%'.$term.'%');
                        }
                    });
                }
            })
            ->when($filters['role'] ?? null, function ($query, $role): void {
                if (Schema::hasTable('lid_rol')) {
                    $query->whereHas('rollen', fn ($roleQuery) => $roleQuery->where('rol.idrol', $role));
                } else {
                    $query->where('rol_idrol', $role);
                }
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('achternaam')->orderBy('idlid')->paginate(12)->withQueryString();
        // Tel hoeveel gebruikers elke rol heeft.
        $roles = Role::query()->orderBy('naam')->get();
        foreach ($roles as $role) {
            $role->member_count = Schema::hasTable('lid_rol')
                ? DB::table('lid_rol')->where('rol_idrol', $role->idrol)->count()
                : DB::table('lid')->where('rol_idrol', $role->idrol)->count();
        }
        $stats = ['members' => User::count(), 'active' => User::where('status', 'actief')->count(), 'roles' => $roles->count()];

        // Tel nieuwe accounts per maand in het gekozen jaar. Een lege maand krijgt 0.
        $year = (int) ($filters['year'] ?? now()->year);
        $monthlyCounts = User::query()
            ->where('aangemaakt_datum', '>=', $year.'-01-01 00:00:00')
            ->where('aangemaakt_datum', '<', ($year + 1).'-01-01 00:00:00')
            ->selectRaw('SUBSTR(aangemaakt_datum, 1, 7) as month, COUNT(*) as total')
            ->groupByRaw('SUBSTR(aangemaakt_datum, 1, 7)')
            ->pluck('total', 'month');
        $monthly = collect(range(1, 12))->map(fn (int $month): int => (int) ($monthlyCounts->get(sprintf('%04d-%02d', $year, $month), 0)))->all();
        $roleDistribution = $roles->map(fn (Role $role): array => ['label' => $role->displayName(), 'value' => (int) $role->member_count])->values();
        $unassigned = Schema::hasTable('lid_rol')
            ? User::query()->whereDoesntHave('rollen')->count()
            : User::whereNull('rol_idrol')->count();
        if ($unassigned > 0) {
            $roleDistribution->push(['label' => 'Geen rol', 'value' => $unassigned]);
        }
        $analytics = [
            'year' => $year,
            'monthly' => $monthly,
            'yearTotal' => array_sum($monthly),
            'activePercent' => $stats['members'] > 0 ? (int) round(100 * $stats['active'] / $stats['members']) : 0,
            'roles' => $roleDistribution->all(),
        ];

        // Geef de gegevens aan de pagina. Blade toont ze in de browser.
        return view('dashboards.applicatiebeheerder', compact('members', 'roles', 'stats', 'filters', 'analytics') + ['roleKey' => 'beheerder']);
    }

    // Open een leeg formulier voor een nieuwe gebruiker.
    public function create(Request $request)
    {
        $this->authorizeAdmin($request);

        return $this->form(new User);
    }

    // Open hetzelfde formulier met de gegevens van deze gebruiker.
    public function edit(Request $request, User $member)
    {
        $this->authorizeAdmin($request);

        return $this->form($member);
    }

    private function form(User $member)
    {
        $roles = Role::orderBy('naam')->get();

        return view('dashboards.admin-member-form', [
            'member' => $member, 'roles' => $roles, 'primaryRoles' => $roles->filter(fn (Role $role): bool => $role->isDashboardRole()), 'roleKey' => 'beheerder',
            'selectedRoleIds' => $member->roleIds(), 'supportsMultipleRoles' => Schema::hasTable('lid_rol'),
            'studentHours' => $member->exists && $member->hasAnyRole(['student']) && Schema::hasTable('urenregistratie')
                ? $this->studentHours($member)->orderByDesc('datum')->get() : collect(),
            'availablePhases' => Schema::hasTable('fase') ? DB::table('fase')->orderBy('volgorde')->get() : collect(),
            'assignedPhases' => $member->exists && Schema::hasTable('student_phase_assignments')
                ? DB::table('student_phase_assignments')->join('fase', 'fase.idfase', '=', 'phase_id')->where('member_id', $member->getKey())->get(['fase.naam']) : collect(),
            'hasStudentProfiles' => Schema::hasTable('student'),
            'studentProfile' => $member->exists && Schema::hasTable('student')
                ? DB::table('student')->where('lid_idlid', $member->getKey())->first() : null,
        ]);
    }

    private function studentHours(User $member): Builder
    {
        $query = DB::table('urenregistratie');
        if (Schema::hasColumn('urenregistratie', 'lid_id')) {
            return $query->where('lid_id', $member->getKey());
        }

        return $query->whereIn('student_id', DB::table('student')->where('lid_idlid', $member->getKey())->select('idstudent'));
    }

    public function saveHours(Request $request, User $member): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($member->hasAnyRole(['student']), 403);
        $data = $request->validate([
            'entry_id' => ['nullable', 'integer'],
            'datum' => ['required', 'date', 'before_or_equal:today'],
            'omschrijving' => ['required', 'string', 'max:255'],
            'uren' => ['required', 'numeric', 'min:0.01', 'max:24', 'decimal:0,2'],
            'status' => ['required', Rule::in(['In afwachting', 'Goedgekeurd', 'Afgekeurd'])],
        ]);
        $entryId = $data['entry_id'] ?? null;
        unset($data['entry_id']);
        if ($entryId) {
            $entry = $this->studentHours($member)->where('idurenregistratie', $entryId);
            abort_unless($entry->exists(), 404);
            $entry->update($data);
        } else {
            if (Schema::hasColumn('urenregistratie', 'lid_id')) {
                $data['lid_id'] = $member->getKey();
            } else {
                $studentId = DB::table('student')->where('lid_idlid', $member->getKey())->value('idstudent');
                if (! $studentId) {
                    throw ValidationException::withMessages(['uren' => 'Sla eerst de studentgegevens op om een studentprofiel aan te maken.']);
                }
                $data['student_id'] = $studentId;
            }
            DB::table('urenregistratie')->insert($data);
        }

        return redirect()->route('beheer.members.edit', $member)->with('success', 'Uren opgeslagen. De student ziet de wijziging na het vernieuwen van de pagina.');
    }

    public function assignPhase(Request $request, User $member): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($member->hasAnyRole(['student']), 403);
        $data = $request->validate([
            'phase_id' => ['nullable', 'required_without:phase_name', 'integer', 'exists:fase,idfase'],
            'phase_name' => ['nullable', 'required_without:phase_id', 'prohibits:phase_id', 'string', 'max:50'],
        ]);
        DB::transaction(function () use ($data, $member): void {
            $phaseId = $data['phase_id'] ?? null;
            if (! $phaseId) {
                $phaseId = DB::table('fase')->insertGetId([
                    'naam' => trim($data['phase_name']),
                    'volgorde' => (int) DB::table('fase')->max('volgorde') + 1,
                ], 'idfase');
            }
            DB::table('student_phase_assignments')->insertOrIgnore([
                'member_id' => $member->getKey(), 'phase_id' => $phaseId,
            ]);
        });

        return redirect()->route('beheer.members.edit', $member)->with('success', 'Fase gekoppeld. De student ziet de fase na het vernieuwen van de pagina.');
    }

    // Sla een nieuwe gebruiker op en ga terug naar het dashboard.
    public function store(Request $request)
    {
        $this->authorizeAdmin($request);
        $member = new User;
        DB::transaction(fn () => $this->saveMember($request, $member));

        return redirect()->route('dashboard')->with('success', 'Gebruiker toegevoegd.');
    }

    // Sla wijzigingen op en ga terug naar de lijst met gebruikers.
    public function update(Request $request, User $member)
    {
        $this->authorizeAdmin($request);
        DB::transaction(fn () => $this->saveMember($request, $member));

        return redirect()->to(route('dashboard').'#gebruikers')->with('success', 'Gebruiker bijgewerkt.');
    }

    public function createRole(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'naam' => ['required', 'string', 'max:50', Rule::unique('rol', 'naam')],
            'beschrijving' => ['nullable', 'string', 'max:255'],
        ]);
        $roleData = ['naam' => trim($data['naam'])];
        if (Schema::hasColumn('rol', 'beschrijving')) {
            $roleData['beschrijving'] = $data['beschrijving'] ?? null;
        }
        DB::table('rol')->insert($roleData);

        return redirect()->to(route('dashboard').'#rollen')->with('success', 'Rol toegevoegd. Je kunt deze nu als extra rol toewijzen.');
    }

    // Verwijder een gebruiker. Het eigen account mag niet weg.
    public function destroy(Request $request, User $member)
    {
        $this->authorizeAdmin($request);

        if ($member->is($request->user())) {
            return redirect()->to(route('dashboard').'#gebruikers')
                ->withErrors(['member' => 'Je kunt je eigen account niet verwijderen.']);
        }

        // Stop als deze gebruiker nog een student- of coachprofiel heeft.
        foreach (['student', 'coach'] as $profileTable) {
            if (Schema::hasTable($profileTable) && DB::table($profileTable)->where('lid_idlid', $member->getKey())->exists()) {
                return redirect()->to(route('dashboard').'#gebruikers')
                    ->withErrors(['member' => 'Deze gebruiker heeft nog een student- of coachprofiel en kan daarom niet worden verwijderd.']);
            }
        }

        $member->delete();

        return redirect()->to(route('dashboard').'#gebruikers')->with('success', 'Gebruiker verwijderd.');
    }

    // Gebruik deze controles bij toevoegen en bij aanpassen.
    private function saveMember(Request $request, User $member): void
    {
        $hasStudentProfiles = Schema::hasTable('student');
        $studentProfile = $member->exists && $hasStudentProfiles
            ? DB::table('student')->where('lid_idlid', $member->getKey())->first() : null;
        $data = $request->validate([
            'voornaam' => ['required', 'string', 'max:50'],
            'achternaam' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('lid', 'email')->ignore($member->getKey(), 'idlid')],
            'rol_idrol' => ['required', 'integer', 'exists:rol,idrol'],
            'rol_ids' => [Schema::hasTable('lid_rol') ? 'sometimes' : 'prohibited', 'array'],
            'rol_ids.*' => ['integer', 'distinct', 'exists:rol,idrol'],
            'status' => ['required', Rule::in(['actief', 'inactief'])],
            'password' => [$member->exists ? 'nullable' : 'required', 'string', 'min:12', 'max:128', 'confirmed'],
            'klas' => ['sometimes', 'nullable', 'string', 'max:20'],
            'opleiding' => ['sometimes', 'nullable', 'string', 'max:50'],
            'studentnummer' => $hasStudentProfiles
                ? ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('student', 'studentnummer')->ignore($studentProfile?->idstudent, 'idstudent')]
                : ['prohibited'],
        ]);
        if (! Role::whereKey($data['rol_idrol'])->first()?->isDashboardRole()) {
            throw ValidationException::withMessages(['rol_idrol' => 'Kies een hoofdrol die toegang geeft tot een bestaand dashboard. Nieuwe rollen kun je als extra rol toevoegen.']);
        }
        // De beheerder mag de eigen rol niet veranderen of het eigen account uitzetten.
        $roleIds = array_values(array_unique(array_map('intval', array_merge(
            [(int) $data['rol_idrol']],
            $data['rol_ids'] ?? [],
        ))));
        if ($member->exists && $member->is($request->user())) {
            $currentRoleIds = $member->roleIds();
            sort($currentRoleIds);
            $requestedRoleIds = $roleIds;
            sort($requestedRoleIds);
            if ($requestedRoleIds !== $currentRoleIds || $data['status'] !== 'actief') {
                throw ValidationException::withMessages(['status' => 'Je kunt je eigen rol of actieve status niet wijzigen.']);
            }
        }
        foreach (['voornaam', 'achternaam', 'email', 'rol_idrol', 'status'] as $field) {
            $member->{$field} = $data[$field] ?? null;
        }
        // Maak een hash van een nieuw wachtwoord. Bewaar nooit de gewone tekst.
        // Is het veld bij bewerken leeg? Dan blijft het oude wachtwoord staan.
        if (! empty($data['password'])) {
            $member->wachtwoord = Hash::make($data['password']);
        }
        if (! $member->exists) {
            $member->aangemaakt_datum = now();
        }
        $member->save();
        if (Schema::hasTable('lid_rol')) {
            DB::table('lid_rol')->where('lid_idlid', $member->getKey())->delete();
            DB::table('lid_rol')->insert(array_map(fn (int $roleId): array => [
                'lid_idlid' => $member->getKey(), 'rol_idrol' => $roleId,
            ], $roleIds));
        }
        $roleNames = Role::whereIn('idrol', $roleIds)->pluck('naam')->map(fn ($name): string => strtolower((string) $name));
        if ($roleNames->contains('coach') && Schema::hasTable('coach')
            && ! DB::table('coach')->where('lid_idlid', $member->getKey())->exists()) {
            DB::table('coach')->insert(['lid_idlid' => $member->getKey()]);
        }
        $isStudent = $roleNames->contains('student');
        if ($hasStudentProfiles && ($isStudent || $studentProfile)) {
            $profileData = array_intersect_key($data, array_flip(['klas', 'opleiding', 'studentnummer']));
            if ($studentProfile) {
                if ($profileData !== []) {
                    DB::table('student')->where('idstudent', $studentProfile->idstudent)->update($profileData);
                }
            } else {
                DB::table('student')->insert($profileData + [
                    'lid_idlid' => $member->getKey(), 'startdatum' => today()->toDateString(),
                ]);
            }
        }

        // Koppel alle leeruitkomsten aan leden met de rol Student.
        if (Schema::hasTable('leeruitkomsten_has_lid') && Schema::hasTable('leeruitkomsten')) {
            if ($isStudent) {
                $assignments = DB::table('leeruitkomsten')
                    ->select('leeruitkomsten_id')
                    ->get()
                    ->map(fn ($outcome) => [
                        'leeruitkomsten_id' => $outcome->leeruitkomsten_id,
                        'lid_idlid' => $member->getKey(),
                        'status' => 'Te doen',
                    ])
                    ->all();

                if ($assignments !== []) {
                    DB::table('leeruitkomsten_has_lid')->insertOrIgnore($assignments);
                }
            }
        }
    }
}
