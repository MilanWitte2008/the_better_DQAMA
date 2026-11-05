{{-- Blade-weergave voor het teamleiderdashboard. --}}
@extends('components.layout-dashboard')

@section('title', 'Dashboard teamleider')
@section('welcome-title', 'Goedemiddag, ' . ($user->voornaam ?? 'teamleider') . '!')
@section('welcome-subtitle', 'Bekijk de voortgang van studenten en groepen binnen je team.')

@section('content')
    <section class="panel team-group-panel" id="groepen" data-dashboard-section="groepen">
        <h3>Studenten in deze groep</h3>
        <form class="team-filters" method="GET" action="{{ route('dashboard') }}">
            <label>
                <span>Groep</span>
                <select name="groep">
                    @forelse ($groups as $group)
                        <option value="{{ $group->idgroep }}" @selected($selectedGroup?->idgroep === $group->idgroep)>
                            {{ $group->groepnaam ?: 'Groep ' . $group->idgroep }}
                        </option>
                    @empty
                        <option value="">Geen groepen gekoppeld</option>
                    @endforelse
                </select>
            </label>
            <button class="primary-button team-filter-button" type="submit" @disabled($groups->isEmpty())>Toon groep</button>
        </form>

        @if (session('success'))
            <p class="info-strip" role="status">{{ session('success') }}</p>
        @endif

        <form class="team-action-form" method="POST" action="{{ route('teammanager.groups.store') }}">
            @csrf
            <label for="new-group-name">Nieuwe groep</label>
            <input id="new-group-name" name="groepnaam" type="text" maxlength="45" required value="{{ old('groepnaam') }}" placeholder="Bijvoorbeeld: ICT 2A">
            @error('groepnaam')
                <small class="form-error">{{ $message }}</small>
            @enderror
            <button class="primary-button" type="submit">Groep aanmaken</button>
        </form>

        @if ($selectedGroup)
            <p class="team-selection-summary">
                {{ $selectedGroup->groepnaam ?: 'Groep ' . $selectedGroup->idgroep }} ·
                {{ $students->count() }} {{ $students->count() === 1 ? 'student' : 'studenten' }}
            </p>
            <form class="team-action-form" method="POST" action="{{ route('teammanager.groups.members.store', $selectedGroup->idgroep) }}">
                @csrf
                <label for="group-member">Student toevoegen aan deze groep</label>
                <select id="group-member" name="lid_idlid" required @disabled($availableStudents->isEmpty())>
                    @if ($availableStudents->isNotEmpty())
                        <option value="" disabled @selected(! old('lid_idlid'))>Kies een student</option>
                    @endif
                    @forelse ($availableStudents as $student)
                        <option value="{{ $student->idlid }}" @selected(old('lid_idlid') == $student->idlid)>
                            {{ trim(($student->voornaam ?? '') . ' ' . ($student->achternaam ?? '')) ?: 'Naam ontbreekt' }}
                            · {{ $student->email }}
                        </option>
                    @empty
                        <option value="">Geen actieve studenten meer beschikbaar</option>
                    @endforelse
                </select>
                @error('lid_idlid')
                    <small class="form-error">{{ $message }}</small>
                @enderror
                <button class="primary-button" type="submit" @disabled($availableStudents->isEmpty())>Lid toevoegen</button>
            </form>
            <div class="table-scroll">
                <table class="data-table team-student-table" data-team-student-table>
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Groep</th>
                            <th scope="col">Voortgang</th>
                            <th scope="col">Leeruitkomsten behaald</th>
                            <th scope="col">Laatste update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            <tr>
                                <th scope="row">{{ $student->naam ?: 'Naam ontbreekt' }}</th>
                                <td>{{ $selectedGroup->groepnaam ?: 'Groep ' . $selectedGroup->idgroep }}</td>
                                <td>
                                    <strong class="team-student-progress">{{ $student->voortgang }}%</strong>
                                    <span class="team-progress-track" role="img" aria-label="{{ $student->voortgang }} procent voortgang">
                                        <span style="width: {{ $student->voortgang }}%"></span>
                                    </span>
                                </td>
                                <td>{{ $student->completed_outcomes }} / {{ $student->total_outcomes }}</td>
                                <td>
                                    {{ $student->laatste_update
                                        ? \Illuminate\Support\Carbon::parse($student->laatste_update)->format('d-m-Y')
                                        : 'Geen update' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Er zijn nog geen studenten aan deze groep gekoppeld.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="info-strip">Er zijn nog geen groepen aan jouw teamleideraccount gekoppeld.</p>
        @endif
    </section>

    <section class="panel team-outcomes-panel" data-dashboard-section="groepen">
        <h3>Voortgang per leeruitkomst (groep)</h3>
        @forelse ($outcomeProgress as $outcome)
            <div class="team-outcome-row">
                <div>
                    <span>
                        <strong class="team-outcome-title">{{ $outcome->label ?: 'Leeruitkomst zonder titel' }}</strong>
                        @if ($outcome->code)
                            <small class="team-outcome-code">{{ $outcome->code }}</small>
                        @endif
                    </span>
                    <strong>{{ $outcome->progress }}%</strong>
                </div>
                @if ($outcome->description)
                    <p class="team-outcome-description">{{ $outcome->description }}</p>
                @endif
                <div class="team-outcome-track" role="img" aria-label="{{ $outcome->label }}: {{ $outcome->progress }} procent">
                    <span style="width: {{ $outcome->progress }}%"></span>
                </div>
            </div>
        @empty
            <p class="muted-line">Er zijn nog geen leeruitkomsten voor deze groep geregistreerd.</p>
        @endforelse
    </section>

    {{-- Toon de huidige voortgang; de database bewaart geen historische voortgangsmetingen. --}}
    <div class="content-grid team-insights-grid" data-dashboard-section="groepen">
        <section class="panel team-trend-panel">
            <h3>Trend: gemiddelde voortgang</h3>
            @if ($selectedGroup && $students->isNotEmpty())
                <div class="team-trend-chart" role="img" aria-label="Huidige gemiddelde groepsvoortgang: {{ $teamProgress }} procent. Historische meetpunten zijn niet beschikbaar.">
                    <div class="team-trend-axis" aria-hidden="true">
                        <span>100%</span>
                        <span>75%</span>
                        <span>50%</span>
                        <span>25%</span>
                    </div>
                    <div class="team-trend-plot" aria-hidden="true">
                        @foreach ([0, 25, 50, 75, 100] as $gridline)
                            <span class="team-trend-gridline" style="top: {{ $gridline }}%"></span>
                        @endforeach
                        <span class="team-trend-point" style="top: {{ 100 - $teamProgress }}%"></span>
                    </div>
                </div>
                <div class="team-trend-current">
                    <strong>{{ $teamProgress }}%</strong>
                    <span>huidige groepsvoortgang</span>
                </div>
                <p class="team-chart-note">Historische voortgangsmetingen zijn niet opgeslagen.</p>
            @else
                <p class="muted-line">Er zijn nog geen groepsgegevens voor een voortgangsoverzicht.</p>
            @endif
        </section>

        {{-- Leerjaar staat niet in de database; toon daarom het echte groepstotaal zonder jaren te verzinnen. --}}
        <section class="panel team-year-panel">
            <h3>Vergelijking per leerjaar</h3>
            @if ($selectedGroup && $students->isNotEmpty())
                <div class="team-year-row">
                    <div><span>Groep totaal</span><strong>{{ $teamProgress }}%</strong></div>
                    <div class="team-year-track" role="img" aria-label="Groep totaal: {{ $teamProgress }} procent voortgang">
                        <span style="width: {{ $teamProgress }}%"></span>
                    </div>
                </div>
                <p class="team-chart-note">Leerjaar wordt niet in de database vastgelegd; daarom is alleen de groepsvoortgang beschikbaar.</p>
            @else
                <p class="muted-line">Er zijn nog geen groepsgegevens om te vergelijken.</p>
            @endif
        </section>
    </div>

    <div class="content-grid team-report-grid">
        <section class="panel" id="rapportages" data-dashboard-section="rapportages">
            <h3>Rapportages</h3>
            @if ($selectedGroup)
                <ul class="team-report-list">
                    <li>Voortgangsrapport · {{ $selectedGroup->groepnaam ?: 'Groep ' . $selectedGroup->idgroep }}</li>
                    <li>Leeruitkomstenanalyse · {{ $selectedGroup->groepnaam ?: 'Groep ' . $selectedGroup->idgroep }}</li>
                    <li>Groepsoverzicht · {{ $students->count() }} studenten</li>
                </ul>
            @else
                <p class="muted-line">Koppel eerst een groep aan je account om rapportages te bekijken.</p>
            @endif
        </section>
        <section class="panel" id="trends" data-dashboard-section="trends">
            <h3>Recente signalen</h3>
            @if ($selectedGroup && $students->isNotEmpty())
                <ul class="team-signal-list">
                    @if ($attentionCount > 0)
                        <li class="team-signal-warning">⚠ {{ $attentionCount }} leeruitkomsten hebben minder dan 60% voortgang.</li>
                    @else
                        <li class="team-signal-info">Er zijn geen leeruitkomsten onder de 60% voortgang.</li>
                    @endif
                    <li class="team-signal-info">› De gemiddelde voortgang in deze groep is {{ $teamProgress }}%.</li>
                </ul>
            @else
                <p class="muted-line">Signalen verschijnen zodra er studenten aan een groep zijn gekoppeld.</p>
            @endif
        </section>
    </div>

    <section class="panel" id="rapportage-download" data-dashboard-section="rapportages">
        <h3>Rapportage</h3>
        <p class="team-panel-description">Download de studentvoortgang van de geselecteerde groep als CSV-bestand.</p>
        <dl class="team-report-details">
            <div>
                <dt>Groep</dt>
                <dd>{{ $selectedGroup?->groepnaam ?: ($selectedGroup ? 'Groep ' . $selectedGroup->idgroep : 'Geen groep gekoppeld') }}</dd>
            </div>
            <div><dt>Studenten</dt><dd>{{ $students->count() }}</dd></div>
            <div><dt>Gemiddelde voortgang</dt><dd>{{ $teamProgress }}%</dd></div>
        </dl>
        <button class="primary-button team-download-button" type="button" data-team-download @disabled($students->isEmpty())>Rapportage downloaden (CSV)</button>
    </section>

    <section class="panel" id="instellingen" data-dashboard-section="instellingen">
        <h3>Instellingen</h3>
        <dl class="team-settings-list">
            <div>
                <dt>Geselecteerde groep</dt>
                <dd>{{ $selectedGroup?->groepnaam ?: ($selectedGroup ? 'Groep ' . $selectedGroup->idgroep : 'Geen groep gekoppeld') }}</dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd>{{ $selectedGroup?->status ?: 'Niet opgegeven' }}</dd>
            </div>
            <div><dt>Signalen tonen</dt><dd>Alle prioriteiten</dd></div>
        </dl>
        <p class="team-settings-note">De groepsgegevens en voortgang worden uit de database geladen.</p>
    </section>

    <script>
        const teamDownloadButton = document.querySelector('[data-team-download]');
        const teamStudentTable = document.querySelector('[data-team-student-table]');

        teamDownloadButton?.addEventListener('click', () => {
            const rows = [
                ['Student', 'Groep', 'Voortgang', 'Leeruitkomsten behaald', 'Laatste update'],
                ...Array.from(teamStudentTable.querySelectorAll('tbody tr'))
                    .filter((row) => row.cells.length === 5)
                    .map((row) => Array.from(row.cells, (cell) => cell.innerText.trim().replace(/\s+/g, ' '))),
            ];
            const csv = `\uFEFF${rows.map((row) => row.map((cell) => `"${cell.replace(/"/g, '""')}"`).join(';')).join('\r\n')}`;
            const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
            const link = document.createElement('a');

            link.href = url;
            link.download = 'groepsrapportage.csv';
            link.click();
            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
        });
    </script>
@endsection
