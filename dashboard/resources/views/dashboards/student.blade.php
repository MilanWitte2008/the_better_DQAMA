{{-- Studentdashboard met gegevens die aan het ingelogde lid gekoppeld zijn. --}}
@extends('components.layout-dashboard')

@php
    // ID van de ingelogde student.
    $memberId = $user->getKey();

    // Het schooljaar begint in augustus.
    $schoolYearStart = now()->month >= 8 ? now()->year : now()->year - 1;
    $schoolYear = $schoolYearStart . '–' . ($schoolYearStart + 1);
    $db = \Illuminate\Support\Facades\DB::connection();
    $schema = $db->getSchemaBuilder();
    $phaseColumn = $schema->hasColumn('leeruitkomsten', 'fase_idfase1') ? 'fase_idfase1' : 'fase_idfase';

    // Haal persoonlijke leerdoelen op als de tabel aan dit lid gekoppeld is.
    $goalTitleColumn = 'titel';
    $goalIdColumn = null;
    $personalGoals = collect();
    if ($schema->hasTable('doelen') && $schema->hasColumn('doelen', 'lid_idlid')) {
        $goalTitleColumn = $schema->hasColumn('doelen', 'titel') ? 'titel' : 'naam';
        $goalPrimary = collect($schema->getIndexes('doelen'))->first(fn (array $index) => $index['primary'] ?? false);
        $goalIdColumn = $goalPrimary['columns'][0] ?? null;
        if ($schema->hasColumn('doelen', $goalTitleColumn)) {
            $personalGoals = $db->table('doelen')->where('lid_idlid', $memberId)->orderBy($goalTitleColumn)->get();
        }
    }

    // Toon de resultaten uit het schema dat ook door het docentdashboard wordt bijgewerkt.
    $outcomes = collect();
    $usingModernOutcomes = false;
    $studentId = $schema->hasTable('student')
        && $schema->hasColumn('student', 'lid_idlid')
        && $schema->hasColumn('student', 'idstudent')
        ? $db->table('student')->where('lid_idlid', $memberId)->value('idstudent')
        : null;
    $hasModernOutcomes = $studentId
        && $schema->hasTable('student_leeruitkomsten')
        && $schema->hasColumn('student_leeruitkomsten', 'student_idstudent')
        && $schema->hasColumn('student_leeruitkomsten', 'leeruitkomsten_leeruitkomsten_id')
        && $schema->hasTable('leeruitkomsten');
    if ($hasModernOutcomes) {
        $modernOutcomeColumns = ['outcome.titel', 'assignment.status'];
        $modernOutcomeColumns[] = $schema->hasColumn('leeruitkomsten', 'omschrijving')
            ? 'outcome.omschrijving'
            : \Illuminate\Support\Facades\DB::raw('NULL AS omschrijving');
        $modernOutcomeColumns[] = $schema->hasColumn('student_leeruitkomsten', 'opmerking')
            ? 'assignment.opmerking'
            : \Illuminate\Support\Facades\DB::raw('NULL AS opmerking');
        if ($schema->hasColumn('student_leeruitkomsten', 'laatste_update')) {
            $modernOutcomeColumns[] = 'assignment.laatste_update';
        }
        $modernOutcomes = $db->table('student_leeruitkomsten as assignment')
            ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_leeruitkomsten_id')
            ->where('assignment.student_idstudent', $studentId)
            ->select($modernOutcomeColumns)
            ->orderBy('outcome.titel')
            ->get();
        if ($modernOutcomes->isNotEmpty()) {
            $outcomes = $modernOutcomes;
            $usingModernOutcomes = true;
        }
    }

    if ($outcomes->isEmpty() && $schema->hasTable('leeruitkomsten_has_lid')) {
        $legacyOutcomeColumns = ['outcome.titel', 'assignment.status'];
        $legacyOutcomeColumns[] = $schema->hasColumn('leeruitkomsten', 'omschrijving')
            ? 'outcome.omschrijving'
            : \Illuminate\Support\Facades\DB::raw('NULL AS omschrijving');
        $legacyOutcomeColumns[] = \Illuminate\Support\Facades\DB::raw('NULL AS opmerking');
        if ($schema->hasTable('fase') && $schema->hasColumn('leeruitkomsten', $phaseColumn)) {
            $legacyOutcomeColumns[] = 'fase.naam as fase_naam';
        } else {
            $legacyOutcomeColumns[] = \Illuminate\Support\Facades\DB::raw('NULL AS fase_naam');
        }
        $legacyOutcomeQuery = $db->table('leeruitkomsten_has_lid as assignment')
            ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_id')
            ->where('assignment.lid_idlid', $memberId);
        if ($schema->hasTable('fase') && $schema->hasColumn('leeruitkomsten', $phaseColumn)) {
            $legacyOutcomeQuery->leftJoin('fase', 'fase.idfase', '=', "outcome.$phaseColumn")
                ->orderByRaw('CASE WHEN fase.volgorde IS NULL THEN 1 ELSE 0 END')
                ->orderBy('fase.volgorde');
        }
        $outcomes = $legacyOutcomeQuery
            ->orderByRaw("FIELD(assignment.status, 'Bezig', 'Te doen', 'Behaald')")
            ->orderBy('outcome.titel')
            ->select($legacyOutcomeColumns)
            ->get();
    }

    $outcomeProgress = (object) [
        'total' => $outcomes->count(),
        'completed' => $outcomes->where('status', 'Behaald')->count(),
    ];

    // Haal de urenregistraties op, nieuwste eerst.
    $hours = $db->table('urenregistratie')
        ->where('lid_id', $memberId)
        ->orderByDesc('datum')
        ->get();

    // Haal de fases van de student op.
    $phases = collect();
    if (! $usingModernOutcomes
        && $schema->hasTable('leeruitkomsten_has_lid')
        && $schema->hasTable('fase')
        && $schema->hasColumn('leeruitkomsten', $phaseColumn)) {
        $phases = $db->table('leeruitkomsten_has_lid as assignment')
            ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_id')
            ->join('fase', 'fase.idfase', '=', "outcome.$phaseColumn")
            ->where('assignment.lid_idlid', $memberId)
            ->select('fase.idfase', 'fase.naam', 'fase.volgorde')
            ->selectRaw('COUNT(*) AS outcome_count, SUM(CASE WHEN assignment.status = ? THEN 1 ELSE 0 END) AS completed_count', ['Behaald'])
            ->groupBy('fase.idfase', 'fase.naam', 'fase.volgorde')
            ->orderBy('fase.volgorde')
            ->get()
            ->map(function ($phase) {
                $phase->progress = $phase->outcome_count > 0
                    ? (int) round(($phase->completed_count / $phase->outcome_count) * 100)
                    : 0;

                return $phase;
            });
    } elseif ($usingModernOutcomes
        && $schema->hasTable('fase')
        && $schema->hasColumn('leeruitkomsten', $phaseColumn)) {
        $phases = $db->table('student_leeruitkomsten as assignment')
            ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_leeruitkomsten_id')
            ->join('fase', 'fase.idfase', '=', "outcome.$phaseColumn")
            ->where('assignment.student_idstudent', $studentId)
            ->select('fase.idfase', 'fase.naam', 'fase.volgorde')
            ->selectRaw('COUNT(*) AS outcome_count, SUM(CASE WHEN assignment.status = ? THEN 1 ELSE 0 END) AS completed_count', ['Behaald'])
            ->groupBy('fase.idfase', 'fase.naam', 'fase.volgorde')
            ->orderBy('fase.volgorde')
            ->get()
            ->map(function ($phase) {
                $phase->progress = $phase->outcome_count > 0
                    ? (int) round(($phase->completed_count / $phase->outcome_count) * 100)
                    : 0;

                return $phase;
            });
    }

    // Haal de vijf nieuwste reflecties op.
    $reflections = $db->table('reflectie')
        ->where('lid_idlid', $memberId)
        ->orderByDesc('datum')
        ->orderByDesc('idreflectie')
        ->get();

    // Ondersteun zowel studentprofielen als gesprekken uit de bestaande ledenkoppeling.
    $conversations = collect();
    $hasConversationFields = $schema->hasTable('gesprekken')
        && $schema->hasColumn('gesprekken', 'datum')
        && $schema->hasColumn('gesprekken', 'onderwerp')
        && $schema->hasColumn('gesprekken', 'inhoud')
        && $schema->hasColumn('gesprekken', 'afspraken');
    if ($hasConversationFields
        && $schema->hasTable('student')
        && $schema->hasColumn('student', 'lid_idlid')
        && $schema->hasColumn('student', 'idstudent')
        && $schema->hasColumn('gesprekken', 'student_idstudent')) {
        $conversations = $db->table('gesprekken as conversation')
            ->join('student', 'student.idstudent', '=', 'conversation.student_idstudent')
            ->where('student.lid_idlid', $memberId)
            ->orderByDesc('conversation.datum')
            ->select('conversation.datum', 'conversation.onderwerp', 'conversation.inhoud', 'conversation.afspraken')
            ->get();
    } elseif ($hasConversationFields
        && $schema->hasTable('gesprekken_has_lid')
        && $schema->hasColumn('gesprekken_has_lid', 'gesprekken_idgesprek')
        && $schema->hasColumn('gesprekken_has_lid', 'lid_idlid')) {
        $conversations = $db->table('gesprekken as conversation')
            ->join('gesprekken_has_lid as participant', 'participant.gesprekken_idgesprek', '=', 'conversation.idgesprek')
            ->where('participant.lid_idlid', $memberId)
            ->orderByDesc('conversation.datum')
            ->select('conversation.datum', 'conversation.onderwerp', 'conversation.inhoud', 'conversation.afspraken')
            ->distinct()
            ->get();
    }

    // Toon alleen notities die rechtstreeks aan deze student gekoppeld zijn.
    $notes = collect();
    if ($schema->hasTable('begeleidingsnotitie')
        && $schema->hasColumn('begeleidingsnotitie', 'lid_idlid')) {
        $notes = $db->table('begeleidingsnotitie')
            ->where('lid_idlid', $memberId)
            ->orderByDesc('datum')
            ->get();
    } elseif ($schema->hasTable('begeleidingsnotitie')
        && $schema->hasColumn('begeleidingsnotitie', 'idstudent')
        && $schema->hasTable('student')
        && $schema->hasColumn('student', 'idstudent')
        && $schema->hasColumn('student', 'lid_idlid')) {
        $notes = $db->table('begeleidingsnotitie as note')
            ->join('student', 'student.idstudent', '=', 'note.idstudent')
            ->where('student.lid_idlid', $memberId)
            ->orderByDesc('note.datum')
            ->select('note.id_notitie', 'note.inhoud', 'note.datum', 'note.type')
            ->get();
    }

    // Haal goedgekeurde uren van de laatste zes maanden op.
    $monthStart = now()->startOfMonth()->subMonths(5);
    $monthlyHours = $db->table('urenregistratie')
        ->where('lid_id', $memberId)
        ->where('status', 'Goedgekeurd')
        ->whereBetween('datum', [$monthStart->toDateString(), now()->toDateString()])
        ->selectRaw("DATE_FORMAT(datum, '%Y-%m') AS maand, SUM(uren) AS totaal")
        ->groupByRaw("DATE_FORMAT(datum, '%Y-%m')")
        ->pluck('totaal', 'maand');

    // Voeg ook maanden zonder uren toe aan de grafiek.
    $hoursByMonth = collect(range(0, 5))->map(function ($offset) use ($monthStart, $monthlyHours) {
        $month = $monthStart->copy()->addMonths($offset);
        $key = $month->format('Y-m');

        return [
            'label' => $month->locale('nl')->translatedFormat('M'),
            'hours' => (float) $monthlyHours->get($key, 0),
        ];
    });

    // Bereken de hoogte van elke maandbalk.
    $maxMonthHours = max(1, (float) $hoursByMonth->max('hours'));
    $hoursByMonth = $hoursByMonth->map(function (array $month) use ($maxMonthHours) {
        $month['percentage'] = $month['hours'] > 0
            ? max(3, (int) round(($month['hours'] / $maxMonthHours) * 100))
            : 0;
        return $month;
    });

    // Bewaar de gegevens voor de pagina.
    $studentData = [
        'hours' => $hours,
        'totalHours' => (float) $hours->sum(fn ($entry) => (float) $entry->uren),
        'approvedHours' => (float) $hours->where('status', 'Goedgekeurd')->sum('uren'),
        'pendingHours' => (float) $hours->where('status', 'In afwachting')->sum('uren'),
        'hoursByMonth' => $hoursByMonth,
        'phases' => $phases,
        'reflections' => $reflections,
        'personalGoals' => $personalGoals,
        'goalIdColumn' => $goalIdColumn,
        'outcomes' => $outcomes,
        'goalTitleColumn' => $goalTitleColumn,
        'conversations' => $conversations,
        'notes' => $notes,
    ];
    $progressPercent = $outcomeProgress->total > 0
        ? (int) round(($outcomeProgress->completed / $outcomeProgress->total) * 100)
        : 0;
@endphp

@section('title', 'Studentdashboard')
@section('welcome-title', 'Goedemiddag, ' . ($user->voornaam ?? 'student') . '!')
@section('welcome-subtitle', 'Je totale voortgang is ' . $progressPercent . '%. Hier zie je jouw ontwikkeling, leerdoelen en uren.')

@section('content')
    @if (session('success'))
        <p class="info-strip" role="status">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <div class="info-strip" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    {{-- Toon de belangrijkste gegevens over de ontwikkeling van de student. --}}
    <div class="section-heading" id="ontwikkeling" data-dashboard-section="ontwikkeling">
        <div>
            <h2>Mijn ontwikkeling</h2>
            <p>Bekijk je voortgang, leerdoelen en uren op één plek.</p>
        </div>
        <span class="period-pill">Schooljaar {{ $schoolYear }}</span>
    </div>

    <section class="stat-grid student-summary-grid" aria-label="Samenvatting" data-dashboard-section="ontwikkeling">
        <article class="stat-card stat-blue">
            <div class="stat-icon">◷</div>
            <div>
                <span>Geregistreerde uren</span>
                <strong>{{ number_format($studentData['totalHours'], $studentData['totalHours'] == (int) $studentData['totalHours'] ? 0 : 1, ',', '.') }} uur</strong>
                <small>{{ number_format($studentData['approvedHours'], 1, ',', '.') }} goedgekeurd · {{ number_format($studentData['pendingHours'], 1, ',', '.') }} in afwachting</small>
            </div>
        </article>
        <article class="stat-card stat-purple">
            <div class="stat-icon">◉</div>
            <div>
                <span>Voortgang fase</span>
                <strong>{{ $studentData['phases']->last()->naam ?? 'Geen fase' }}</strong>
                <small>{{ $studentData['phases']->count() }} fases aan jouw account gekoppeld</small>
            </div>
        </article>
    </section>

    {{-- Laat per fase zien hoeveel leeruitkomsten zijn behaald. --}}
    <section class="panel" data-dashboard-section="ontwikkeling">
        <h3>Mijn fases</h3>
        @if ($studentData['phases']->isNotEmpty())
            <div class="phase-grid">
                @foreach ($studentData['phases'] as $phase)
                    @php
                        $phaseStyle = ['phase-green', 'phase-blue', 'phase-orange'][$loop->index % 3];
                    @endphp
                    <div class="phase {{ $phaseStyle }}">
                        <div class="ring phase-ring" style="--progress: {{ $phase->progress }}%" role="img" aria-label="{{ $phase->progress }} procent voortgang in deze fase">
                            <span aria-hidden="true">{{ $phase->progress }}%</span>
                        </div>
                        <strong>{{ $phase->naam }}</strong>
                    </div>
                @endforeach
            </div>
        @else
            <p class="info-strip">Er zijn nog geen fases aan jouw account gekoppeld.</p>
        @endif
    </section>

        <section class="panel wide-panel" id="uren" data-dashboard-section="uren">
            <h3>Uren &amp; resultaten</h3>
            <div class="hours-total">{{ number_format($studentData['totalHours'], $studentData['totalHours'] == (int) $studentData['totalHours'] ? 0 : 1, ',', '.') }} <span>uur geregistreerd</span></div>
            <p class="muted-line">Goedgekeurde uren per maand in de afgelopen zes maanden</p>
            <div class="bar-chart" aria-label="Goedgekeurde uren per maand">
                @foreach ($studentData['hoursByMonth'] as $month)
                    <i style="height:{{ $month['percentage'] }}%" title="{{ number_format($month['hours'], 1, ',', '.') }} uur">
                        <b>{{ $month['label'] }}</b>
                    </i>
                @endforeach
            </div>
            @if ($studentData['hours']->isEmpty())
                <p class="info-strip">Er zijn nog geen uren aan jouw account gekoppeld.</p>
            @endif
        </section>

    {{-- Toon persoonlijke leerdoelen en de gekoppelde leeruitkomsten. --}}
    <div class="content-grid">
        <section class="panel" id="leerdoelen" data-dashboard-section="leerdoelen">
            <h3>Mijn leerdoelen</h3>
            <p class="info-strip">Houd je persoonlijke doelen bij. Je kunt je eigen leerdoelen aanpassen of verwijderen.</p>
            @if ($studentData['personalGoals']->isNotEmpty())
                <ul class="personal-goal-list {{ $studentData['personalGoals']->count() > 5 ? 'personal-goal-list-scroll' : '' }}">
                    @foreach ($studentData['personalGoals'] as $goal)
                        <li class="student-record">
                            <span>{{ $goal->{$studentData['goalTitleColumn']} }}<small>Persoonlijk leerdoel</small></span>
                            @if ($studentData['goalIdColumn'])
                                <details class="student-record-actions">
                                    <summary class="record-toggle">Aanpassen of verwijderen</summary>
                                    <form class="simple-form" method="POST" action="{{ route('student.goals.update', $goal->{$studentData['goalIdColumn']}) }}">
                                        @csrf
                                        @method('PUT')
                                        <label class="sr-only" for="goal-{{ $goal->{$studentData['goalIdColumn']} }}">Leerdoel aanpassen</label>
                                        <input id="goal-{{ $goal->{$studentData['goalIdColumn']} }}" type="text" name="titel" maxlength="255" required value="{{ $goal->{$studentData['goalTitleColumn']} }}">
                                        <button class="record-button" type="submit">Aanpassen</button>
                                    </form>
                                    <form method="POST" action="{{ route('student.goals.destroy', $goal->{$studentData['goalIdColumn']}) }}" onsubmit="return confirm('Weet je zeker dat je dit leerdoel wilt verwijderen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="record-button record-delete" type="submit">Verwijderen</button>
                                    </form>
                                </details>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="muted-line">Je hebt nog geen persoonlijke leerdoelen opgeslagen.</p>
            @endif
            <form class="simple-form student-entry-form" method="POST" action="{{ route('student.goals.store') }}">
                @csrf
                <label for="goal-title">Nieuw leerdoel</label>
                <input id="goal-title" type="text" name="titel" maxlength="255" required value="{{ old('titel') }}" placeholder="Bijvoorbeeld: presenteren verbeteren">
                <button class="primary-button" type="submit">Leerdoel opslaan</button>
            </form>
        </section>

        <section class="panel" id="resultaten" data-dashboard-section="resultaten">
            <h3>Mijn resultaten</h3>
            @if ($studentData['outcomes']->isNotEmpty())
                <div class="scroll-field">
                    <ul class="goal-list">
                        @foreach ($studentData['outcomes'] as $outcome)
                            <li class="goal-item">
                                <button
                                    class="goal-trigger"
                                    type="button"
                                    data-outcome-title="{{ $outcome->titel ?: 'Leeruitkomst zonder titel' }}"
                                    data-outcome-status="{{ $outcome->status ?: 'Onbekend' }}"
                                    data-outcome-description="{{ $outcome->omschrijving ?? '' }}"
                                    data-outcome-comment="{{ $outcome->opmerking ?? '' }}"
                                    aria-haspopup="dialog"
                                    aria-controls="outcome-dialog"
                                >
                                    <span class="goal-copy">
                                        <strong>{{ $outcome->titel ?: 'Leeruitkomst zonder titel' }}</strong>
                                    </span>
                                    <span class="goal-tag">{{ $outcome->status ?: 'Onbekend' }}</span>
                                </button>
                                @if ($outcome->omschrijving)
                                    <small class="widget-detail">{{ $outcome->omschrijving }}</small>
                                @endif
                                @if ($outcome->opmerking)
                                    <small class="widget-detail">Feedback van je coach: {{ $outcome->opmerking }}</small>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="info-strip">Er zijn nog geen leeruitkomsten aan jouw account gekoppeld.</p>
            @endif
        </section>
    </div>

    <dialog class="outcome-dialog" id="outcome-dialog" aria-labelledby="outcome-dialog-title">
        <div class="outcome-dialog-header">
            <h2 id="outcome-dialog-title"></h2>
            <button class="outcome-dialog-close" type="button" aria-label="Sluiten" data-outcome-dialog-close>&times;</button>
        </div>
        <p class="outcome-dialog-status" id="outcome-dialog-status"></p>
        <p class="outcome-dialog-description" id="outcome-dialog-description"></p>
        <p class="outcome-dialog-description" id="outcome-dialog-comment"></p>
    </dialog>

    <script>
        const outcomeDialog = document.getElementById('outcome-dialog');
        const outcomeDialogTitle = document.getElementById('outcome-dialog-title');
        const outcomeDialogStatus = document.getElementById('outcome-dialog-status');
        const outcomeDialogDescription = document.getElementById('outcome-dialog-description');
        const outcomeDialogComment = document.getElementById('outcome-dialog-comment');

        document.querySelectorAll('.goal-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                outcomeDialogTitle.textContent = trigger.dataset.outcomeTitle;
                outcomeDialogStatus.textContent = trigger.dataset.outcomeStatus;
                outcomeDialogDescription.textContent = trigger.dataset.outcomeDescription || 'Geen omschrijving beschikbaar.';
                outcomeDialogComment.textContent = trigger.dataset.outcomeComment ? `Feedback van je coach: ${trigger.dataset.outcomeComment}` : '';
                outcomeDialog.showModal();
            });
        });

        document.querySelector('[data-outcome-dialog-close]').addEventListener('click', () => {
            outcomeDialog.close();
        });

        outcomeDialog.addEventListener('click', (event) => {
            if (event.target === outcomeDialog) {
                outcomeDialog.close();
            }
        });
    </script>

    {{-- Toon coachgesprekken die aan de student gekoppeld zijn. --}}
    <section class="panel" id="gesprekken" data-dashboard-section="gesprekken">
        <h3>Coachgesprekken</h3>
        @if ($studentData['conversations']->isNotEmpty())
            <ul class="updates-list">
                @foreach ($studentData['conversations'] as $conversation)
                    <li>
                        @if ($conversation->onderwerp === \App\DocentDashboard::MEETING_REQUEST_TOPIC)
                            <strong>Gespreksaanvraag:
                                @switch($conversation->afspraken)
                                    @case('approved') Goedgekeurd @break
                                    @case('rejected') Afgekeurd @break
                                    @default In afwachting van de docent
                                @endswitch
                            </strong>
                            @if ($conversation->datum)
                                <small class="widget-detail">Voorgestelde datum: {{ \Illuminate\Support\Carbon::parse($conversation->datum)->format('d-m-Y H:i') }}</small>
                            @endif
                        @else
                            @if ($conversation->datum)<time>{{ \Illuminate\Support\Carbon::parse($conversation->datum)->format('d-m-Y') }}</time><br>@endif
                            <strong>{{ $conversation->onderwerp ?: 'Coachgesprek' }}</strong>
                        @endif
                        @if ($conversation->inhoud)<small class="widget-detail">{{ $conversation->inhoud }}</small>@endif
                        @if ($conversation->afspraken && $conversation->onderwerp !== \App\DocentDashboard::MEETING_REQUEST_TOPIC)<small class="widget-detail">Afspraken: {{ $conversation->afspraken }}</small>@endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="info-strip">Er zijn nog geen coachgesprekken aan jouw account gekoppeld.</p>
        @endif
        <details class="meeting-request" @if ($errors->has('docent_id') || $errors->has('datum') || $errors->has('gesprekaanvraag')) open @endif>
            <summary class="primary-button meeting-request-toggle">Gesprek aanvragen</summary>
            <p class="muted-line">Kies met welke docent je een gesprek wilt aanvragen.</p>
            <form class="simple-form student-entry-form" method="POST" action="{{ route('student.meetings.request') }}">
                @csrf
                <label for="meeting-request-coach">Docent</label>
                @if ($coaches->isNotEmpty())
                    <select id="meeting-request-coach" name="docent_id" required>
                        <option value="">Kies een docent</option>
                        @foreach ($coaches as $coach)
                            <option value="{{ $coach->docent_id }}" @selected(old('docent_id') == $coach->docent_id)>
                                {{ trim($coach->voornaam.' '.$coach->achternaam) }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <select id="meeting-request-coach" disabled>
                        <option>Geen actieve docent gekoppeld</option>
                    </select>
                    <p class="muted-line">Er is geen actieve docent aan jouw account gekoppeld.</p>
                @endif
                <label for="meeting-request-date">Voorgestelde datum en tijd</label>
                <input id="meeting-request-date" type="datetime-local" name="datum" required min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('datum') }}">
                <label for="meeting-request-message">Bericht aan de docent (optioneel)</label>
                <textarea id="meeting-request-message" name="bericht" rows="3" maxlength="1000" placeholder="Waarover wil je praten?">{{ old('bericht') }}</textarea>
                <button class="primary-button" type="submit" @disabled($coaches->isEmpty())>Aanvraag versturen</button>
            </form>
        </details>
    </section>
    <section class="panel" id="notities" data-dashboard-section="notities">
        <h3>Notities van je docent</h3>
        @if ($studentData['notes']->isNotEmpty())
            <ul class="updates-list">
                @foreach ($studentData['notes'] as $note)
                    <li>
                        @if ($note->datum)<time>{{ \Illuminate\Support\Carbon::parse($note->datum)->format('d-m-Y') }}</time><br>@endif
                        @if ($note->type)<strong>{{ ucfirst($note->type) }}</strong><br>@endif
                        {{ $note->inhoud ?: 'Notitie zonder inhoud' }}
                    </li>
                @endforeach
            </ul>
        @else
            <p class="info-strip">Er zijn nog geen notities aan jouw account gekoppeld.</p>
        @endif
    </section>
        {{-- Toon recente reflecties en de urenregistratie van de afgelopen maanden. --}}
    <div class="content-grid">
        <section class="panel" id="reflecties" data-dashboard-section="reflecties">
            <h3>Reflecties</h3>
            @if ($studentData['reflections']->isNotEmpty())
                <ul class="updates-list">
                    @foreach ($studentData['reflections'] as $reflection)
                        <li class="student-reflection-item">
                            <div>
                                @if ($reflection->datum)
                                    <time>{{ \Illuminate\Support\Carbon::parse($reflection->datum)->format('d-m-Y') }}</time><br>
                                @endif
                                {{ $reflection->inhoud ?: 'Reflectie zonder inhoud' }}
                            </div>
                            <details class="student-record-actions">
                                <summary class="record-toggle">Aanpassen of verwijderen</summary>
                                <form class="simple-form" method="POST" action="{{ route('student.reflections.update', $reflection->idreflectie) }}">
                                    @csrf
                                    @method('PUT')
                                    <label class="sr-only" for="reflection-{{ $reflection->idreflectie }}">Reflectie aanpassen</label>
                                    <textarea id="reflection-{{ $reflection->idreflectie }}" name="inhoud" rows="3" maxlength="5000" required>{{ $reflection->inhoud }}</textarea>
                                    <button class="record-button" type="submit">Aanpassen</button>
                                </form>
                                <form method="POST" action="{{ route('student.reflections.destroy', $reflection->idreflectie) }}" onsubmit="return confirm('Weet je zeker dat je deze reflectie wilt verwijderen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="record-button record-delete" type="submit">Verwijderen</button>
                                </form>
                            </details>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="info-strip">Je hebt nog geen reflecties opgeslagen.</p>
            @endif
            <form class="simple-form student-entry-form" method="POST" action="{{ route('student.reflections.store') }}">
                @csrf
                <label for="reflection-content">Nieuwe reflectie</label>
                <textarea id="reflection-content" name="inhoud" rows="4" maxlength="5000" required placeholder="Wat heb je geleerd en wat wil je verder ontwikkelen?">{{ old('inhoud') }}</textarea>
                <button class="primary-button" type="submit">Reflectie bewaren</button>
            </form>
        </section>


    </div>
@endsection
