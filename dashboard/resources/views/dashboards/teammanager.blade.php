{{-- Blade-weergave voor het teamleiderdashboard. --}}
@extends('components.layout-dashboard')

@section('title', 'Dashboard teamleider')
@section('welcome-title', 'Goedemiddag, ' . ($user->voornaam ?? 'teamleider') . '!')
@section('welcome-subtitle', 'Bekijk de voortgang van studenten en groepen binnen je team.')

@section('content')
    @unless ($groupsAvailable)
        <div class="panel" role="status">
            Groepsbeheer is nog niet ingericht in deze database. De tabellen voor groepen en groepsleden ontbreken.
            Zodra deze zijn ingericht, verschijnt hier de voortgang van je gekoppelde groepen.
        </div>
    @endunless
    <section class="panel team-group-panel" id="groepen" data-dashboard-section="groepen">
        <h3>Groepsleden in deze groep</h3>
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

        @if ($selectedGroup)
            <form class="team-group-delete-form" method="POST"
                action="{{ route('teammanager.groups.destroy', $selectedGroup->idgroep) }}"
                onsubmit="return confirm('Weet je zeker dat je deze groep wilt verwijderen? De groepskoppelingen verdwijnen, maar accounts en hun gegevens blijven behouden.')">
                @csrf
                @method('DELETE')
                <button class="team-remove-button" type="submit">Groep verwijderen</button>
            </form>
        @endif

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
                {{ $groupMembers->count() }} {{ $groupMembers->count() === 1 ? 'groepslid' : 'groepsleden' }}
            </p>
            <form class="team-action-form" method="POST" action="{{ route('teammanager.groups.members.store', $selectedGroup->idgroep) }}">
                @csrf
                <label for="group-member">Student of docent toevoegen aan deze groep</label>
                <select id="group-member" name="lid_idlid" required @disabled($availableStudents->isEmpty() && $availableTeachers->isEmpty())>
                    @if ($availableStudents->isNotEmpty() || $availableTeachers->isNotEmpty())
                        <option value="" disabled @selected(! old('lid_idlid'))>Kies een student of docent</option>
                    @endif
                    @if ($availableStudents->isNotEmpty())
                        <optgroup label="Studenten">
                            @foreach ($availableStudents as $student)
                                <option value="{{ $student->idlid }}" @selected(old('lid_idlid') == $student->idlid)>
                                    {{ trim(($student->voornaam ?? '') . ' ' . ($student->achternaam ?? '')) ?: 'Naam ontbreekt' }}
                                    · {{ $student->email }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if ($availableTeachers->isNotEmpty())
                        <optgroup label="Docenten">
                            @foreach ($availableTeachers as $teacher)
                                <option value="{{ $teacher->idlid }}" @selected(old('lid_idlid') == $teacher->idlid)>
                                    {{ trim(($teacher->voornaam ?? '') . ' ' . ($teacher->achternaam ?? '')) ?: 'Naam ontbreekt' }}
                                    · {{ $teacher->email }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if ($availableStudents->isEmpty() && $availableTeachers->isEmpty())
                        <option value="">Geen actieve studenten of docenten meer beschikbaar</option>
                    @endif
                </select>
                @error('lid_idlid')
                    <small class="form-error">{{ $message }}</small>
                @enderror
                <button class="primary-button" type="submit" @disabled($availableStudents->isEmpty() && $availableTeachers->isEmpty())>Lid toevoegen</button>
            </form>
            <div class="table-scroll">
                <table class="data-table team-student-table" data-team-student-table>
                    <thead>
                        <tr>
                            <th scope="col">Groepslid</th>
                            <th scope="col">Groep</th>
                            <th scope="col">Voortgang</th>
                            <th scope="col">Leeruitkomsten behaald</th>
                            <th scope="col">Laatste update</th>
                            <th scope="col">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groupMembers as $member)
                            <tr @if ($member->rolnaam === 'Student') data-report-row @endif>
                                <th scope="row" @if ($member->rolnaam === 'Student') data-report-name="{{ $member->naam ?: 'Naam ontbreekt' }}" @endif>
                                    {{ $member->naam ?: 'Naam ontbreekt' }}
                                    <small>{{ $member->rolnaam }}</small>
                                </th>
                                <td>{{ $selectedGroup->groepnaam ?: 'Groep ' . $selectedGroup->idgroep }}</td>
                                @if ($member->rolnaam === 'Student')
                                    <td>
                                        <strong class="team-student-progress">{{ $member->voortgang }}%</strong>
                                        <span class="team-progress-track" role="img" aria-label="{{ $member->voortgang }} procent voortgang">
                                            <span style="width: {{ $member->voortgang }}%"></span>
                                        </span>
                                    </td>
                                    <td>{{ $member->completed_outcomes }} / {{ $member->total_outcomes }}</td>
                                    <td>
                                        {{ $member->laatste_update
                                            ? \Illuminate\Support\Carbon::parse($member->laatste_update)->format('d-m-Y')
                                            : 'Geen update' }}
                                    </td>
                                @else
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                @endif
                                <td>
                                    <form method="POST"
                                        action="{{ route('teammanager.groups.members.destroy', [$selectedGroup->idgroep, $member->idlid]) }}"
                                        onsubmit="return confirm('Wil je dit groepslid uit de groep verwijderen? Het account en de gegevens blijven behouden.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="team-remove-button" type="submit"
                                            aria-label="Verwijder {{ $member->naam ?: 'groepslid' }} uit deze groep">
                                            Verwijderen
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Er zijn nog geen studenten of docenten aan deze groep gekoppeld.</td></tr>
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
        @if ($outcomeProgress->isNotEmpty())
            <div class="scroll-field">
                <ul class="goal-list">
                    @foreach ($outcomeProgress as $outcome)
                        <li class="goal-item">
                            <button
                                class="goal-trigger"
                                type="button"
                                data-outcome-title="{{ $outcome->label ?: 'Leeruitkomst zonder titel' }}"
                                data-outcome-status="{{ $outcome->progress }}% behaald binnen de groep"
                                data-outcome-description="{{ $outcome->description ?? '' }}"
                                aria-haspopup="dialog"
                                aria-controls="team-outcome-dialog"
                            >
                                <span class="goal-copy">
                                    <strong>{{ $outcome->label ?: 'Leeruitkomst zonder titel' }}</strong>
                                </span>
                                <span class="goal-tag">{{ $outcome->progress }}%</span>
                            </button>
                            @if ($outcome->description)
                                <small class="widget-detail">{{ $outcome->description }}</small>
                            @endif
                            <small class="widget-detail">{{ $outcome->completed }} / {{ $outcome->total }} studenten hebben deze leeruitkomst behaald.</small>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <p class="muted-line">Er zijn nog geen leeruitkomsten voor deze groep geregistreerd.</p>
        @endif
    </section>

    <dialog class="outcome-dialog" id="team-outcome-dialog" aria-labelledby="team-outcome-dialog-title">
        <div class="outcome-dialog-header">
            <h2 id="team-outcome-dialog-title"></h2>
            <button class="outcome-dialog-close" type="button" aria-label="Sluiten" data-team-outcome-dialog-close>&times;</button>
        </div>
        <p class="outcome-dialog-status" id="team-outcome-dialog-status"></p>
        <p class="outcome-dialog-description" id="team-outcome-dialog-description"></p>
    </dialog>

    <script>
        const teamOutcomeDialog = document.getElementById('team-outcome-dialog');
        const teamOutcomeDialogTitle = document.getElementById('team-outcome-dialog-title');
        const teamOutcomeDialogStatus = document.getElementById('team-outcome-dialog-status');
        const teamOutcomeDialogDescription = document.getElementById('team-outcome-dialog-description');

        document.querySelectorAll('.team-outcomes-panel .goal-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                teamOutcomeDialogTitle.textContent = trigger.dataset.outcomeTitle;
                teamOutcomeDialogStatus.textContent = trigger.dataset.outcomeStatus;
                teamOutcomeDialogDescription.textContent = trigger.dataset.outcomeDescription || 'Geen omschrijving beschikbaar.';
                teamOutcomeDialog.showModal();
            });
        });

        document.querySelector('[data-team-outcome-dialog-close]').addEventListener('click', () => {
            teamOutcomeDialog.close();
        });

        teamOutcomeDialog.addEventListener('click', (event) => {
            if (event.target === teamOutcomeDialog) {
                teamOutcomeDialog.close();
            }
        });
    </script>

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
                    .filter((row) => row.hasAttribute('data-report-row'))
                    .map((row) => Array.from(row.cells).slice(0, 5).map((cell) => (
                        cell.dataset.reportName ?? cell.innerText.trim().replace(/\s+/g, ' ')
                    ))),
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
