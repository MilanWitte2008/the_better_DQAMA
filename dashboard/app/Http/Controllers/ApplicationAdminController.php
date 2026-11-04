<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
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
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('rol_idrol', $role))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('achternaam')->orderBy('idlid')->paginate(12)->withQueryString();
        // Tel hoeveel gebruikers elke rol heeft.
        $roles = Role::query()->withCount(['members as member_count'])->orderBy('naam')->get();
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
        $roleDistribution = $roles->map(fn (Role $role): array => ['label' => $role->naam ?? 'Naamloze rol', 'value' => (int) $role->member_count])->values();
        $unassigned = $stats['members'] - $roles->sum('member_count');
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
        return view('dashboards.admin-member-form', [
            'member' => $member, 'roles' => Role::orderBy('naam')->get(), 'roleKey' => 'beheerder',
        ]);
    }

    // Sla een nieuwe gebruiker op en ga terug naar het dashboard.
    public function store(Request $request)
    {
        $this->authorizeAdmin($request);
        $member = new User;
        $this->saveMember($request, $member);

        return redirect()->route('dashboard')->with('success', 'Gebruiker toegevoegd.');
    }

    // Sla wijzigingen op en ga terug naar de lijst met gebruikers.
    public function update(Request $request, User $member)
    {
        $this->authorizeAdmin($request);
        $this->saveMember($request, $member);

        return redirect()->to(route('dashboard').'#gebruikers')->with('success', 'Gebruiker bijgewerkt.');
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
        $data = $request->validate([
            'voornaam' => ['required', 'string', 'max:50'],
            'achternaam' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('lid', 'email')->ignore($member->getKey(), 'idlid')],
            'rol_idrol' => ['required', 'integer', 'exists:rol,idrol'],
            'status' => ['required', Rule::in(['actief', 'inactief'])],
            'password' => [$member->exists ? 'nullable' : 'required', 'string', 'min:12', 'max:128', 'confirmed'],
        ]);
        // De beheerder mag de eigen rol niet veranderen of het eigen account uitzetten.
        if ($member->exists && $member->is($request->user())) {
            if ((int) $data['rol_idrol'] !== (int) $member->rol_idrol || $data['status'] !== 'actief') {
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
    }

}
