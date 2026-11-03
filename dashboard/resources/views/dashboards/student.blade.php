{{-- Studentdashboard met gegevens die aan het ingelogde lid gekoppeld zijn. --}}
@extends('components.layout-dashboard')

@php
    // ID van de ingelogde student.
    $memberId = $user->getKey();

    // Het schooljaar begint in augustus.
    $schoolYearStart = now()->month >= 8 ? now()->year : now()->year - 1;
    $schoolYear = $schoolYearStart . '–' . ($schoolYearStart + 1);
    $db = \Illuminate\Support\Facades\DB::connection();
    $phaseColumn = $db->getSchemaBuilder()->hasColumn('leeruitkomsten', 'fase_idfase1') ? 'fase_idfase1' : 'fase_idfase';

    // Tel de behaalde leeruitkomsten voor het voortgangspercentage in de header.
    $outcomeProgress = $db->table('leeruitkomsten_has_lid')
        ->where('lid_idlid', $memberId)
        ->selectRaw('COUNT(*) AS total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed', ['Behaald'])
        ->first();

    // Haal persoonlijke leerdoelen op als de tabel aan dit lid gekoppeld is.
    $goalTitleColumn = 'titel';
    $personalGoals = collect();
    if ($db->getSchemaBuilder()->hasTable('doelen') && $db->getSchemaBuilder()->hasColumn('doelen', 'lid_idlid')) {
        $goalTitleColumn = $db->getSchemaBuilder()->hasColumn('doelen', 'titel') ? 'titel' : 'naam';
        if ($db->getSchemaBuilder()->hasColumn('doelen', $goalTitleColumn)) {
            $personalGoals = $db->table('doelen')->where('lid_idlid', $memberId)->orderBy($goalTitleColumn)->get();
        }
    }

    // Sorteer de leeruitkomsten per fase en toon de persoonlijke status.
    $outcomes = $db->table('leeruitkomsten_has_lid as assignment')
        ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_id')
        ->leftJoin('fase', 'fase.idfase', '=', "outcome.$phaseColumn")
        ->where('assignment.lid_idlid', $memberId)
        ->orderByRaw('CASE WHEN fase.volgorde IS NULL THEN 1 ELSE 0 END')
        ->orderBy('fase.volgorde')
        ->orderByRaw("FIELD(assignment.status, 'Bezig', 'Te doen', 'Behaald')")
        ->orderBy('outcome.titel')
        ->select('outcome.titel', 'outcome.omschrijving', 'assignment.status', 'fase.naam as fase_naam')
        ->get();

    // Haal de urenregistraties op, nieuwste eerst.
    $hours = $db->table('urenregistratie')
        ->where('lid_id', $memberId)
        ->orderByDesc('datum')
        ->get();

    // Haal de fases van de student op.
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

    // Haal de vijf nieuwste reflecties op.
    $reflections = $db->table('reflectie')
        ->where('lid_idlid', $memberId)
        ->orderByDesc('datum')
        ->orderByDesc('idreflectie')
        ->limit(5)
        ->get();

    // Haal coachgesprekken alleen op als de benodigde tabellen en kolommen bestaan.
    $conversations = collect();
    if ($db->getSchemaBuilder()->hasTable('gesprekken')
        && $db->getSchemaBuilder()->hasTable('student')
        && $db->getSchemaBuilder()->hasColumn('student', 'lid_idlid')
        && $db->getSchemaBuilder()->hasColumn('student', 'idstudent')
        && $db->getSchemaBuilder()->hasColumn('gesprekken', 'student_idstudent')
        && $db->getSchemaBuilder()->hasColumn('gesprekken', 'datum')
        && $db->getSchemaBuilder()->hasColumn('gesprekken', 'onderwerp')
        && $db->getSchemaBuilder()->hasColumn('gesprekken', 'inhoud')
        && $db->getSchemaBuilder()->hasColumn('gesprekken', 'afspraken')) {
        $conversations = $db->table('gesprekken as conversation')
            ->join('student', 'student.idstudent', '=', 'conversation.student_idstudent')
            ->where('student.lid_idlid', $memberId)
            ->orderByDesc('conversation.datum')
            ->select('conversation.datum', 'conversation.onderwerp', 'conversation.inhoud', 'conversation.afspraken')
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
        'outcomes' => $outcomes,
        'goalTitleColumn' => $goalTitleColumn,
        'conversations' => $conversations,
    ];
    $progressPercent = $outcomeProgress->total > 0
        ? (int) round(($outcomeProgress->completed / $outcomeProgress->total) * 100)
        : 0;
@endphp

@section('title', 'Studentdashboard')
@section('welcome-title', 'Goedemiddag, ' . ($user->voornaam ?? 'student') . '!')
@section('welcome-subtitle', 'Je totale voortgang is ' . $progressPercent . '%. Hier zie je jouw ontwikkeling, leerdoelen en uren.')

@section('content')
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

    <div class="content-grid">
        <section class="panel" id="reflecties" data-dashboard-section="reflecties">
            <h3>Reflecties</h3>
            @if ($studentData['reflections']->isNotEmpty())
                <ul class="updates-list">
                    @foreach ($studentData['reflections'] as $reflection)
                        <li>
                            @if ($reflection->datum)
                                <time>{{ \Illuminate\Support\Carbon::parse($reflection->datum)->format('d-m-Y') }}</time>
                            @endif
                            {{ $reflection->inhoud ?: 'Reflectie zonder inhoud' }}
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
                @error('inhoud')<small class="form-error">{{ $message }}</small>@enderror
                <button class="primary-button" type="submit">Reflectie bewaren</button>
            </form>
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
    </div>

    <div class="content-grid">
        <section class="panel" id="leerdoelen" data-dashboard-section="leerdoelen">
            <h3>Mijn leerdoelen</h3>
            <p class="info-strip">Houd je persoonlijke doelen bij en voeg een nieuw ontwikkelpunt toe.</p>
            @if ($studentData['personalGoals']->isNotEmpty())
                <ul class="personal-goal-list {{ $studentData['personalGoals']->count() > 5 ? 'personal-goal-list-scroll' : '' }}">
                    @foreach ($studentData['personalGoals'] as $goal)
                        <li><span>{{ $goal->{$studentData['goalTitleColumn']} }}<small>Persoonlijk leerdoel</small></span></li>
                    @endforeach
                </ul>
            @else
                <p class="muted-line">Je hebt nog geen persoonlijke leerdoelen opgeslagen.</p>
            @endif
            <form class="simple-form student-entry-form" method="POST" action="{{ route('student.goals.store') }}">
                @csrf
                <label for="goal-title">Nieuw leerdoel</label>
                <input id="goal-title" type="text" name="titel" maxlength="255" required value="{{ old('titel') }}" placeholder="Bijvoorbeeld: presenteren verbeteren">
                @error('titel')<small class="form-error">{{ $message }}</small>@enderror
                <button class="primary-button" type="submit">Leerdoel opslaan</button>
            </form>
        </section>

        <section class="panel" id="leeruitkomsten" data-dashboard-section="leeruitkomsten">
            <h3>Leeruitkomsten</h3>
            @if ($studentData['outcomes']->isNotEmpty())
                <div class="scroll-field">
                    <ul class="goal-list">
                        @foreach ($studentData['outcomes'] as $outcome)
                            <li>
                                <span>
                                    <strong>{{ $outcome->titel ?: 'Leeruitkomst zonder titel' }}</strong>
                                    @if ($outcome->omschrijving)
                                        <small class="widget-detail">{{ $outcome->omschrijving }}</small>
                                    @endif
                                </span>
                                <span class="goal-tag">{{ $outcome->status ?: 'Onbekend' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="info-strip">Er zijn nog geen leeruitkomsten aan jouw account gekoppeld.</p>
            @endif
        </section>
    </div>

    <section class="panel" id="gesprekken" data-dashboard-section="gesprekken">
        <h3>Coachgesprekken</h3>
        @if ($studentData['conversations']->isNotEmpty())
            <ul class="updates-list">
                @foreach ($studentData['conversations'] as $conversation)
                    <li>
                        @if ($conversation->datum)<time>{{ \Illuminate\Support\Carbon::parse($conversation->datum)->format('d-m-Y') }}</time><br>@endif
                        <strong>{{ $conversation->onderwerp ?: 'Coachgesprek' }}</strong>
                        @if ($conversation->inhoud)<small class="widget-detail">{{ $conversation->inhoud }}</small>@endif
                        @if ($conversation->afspraken)<small class="widget-detail">Afspraken: {{ $conversation->afspraken }}</small>@endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="info-strip">Er zijn nog geen coachgesprekken aan jouw account gekoppeld.</p>
        @endif
    </section>
@endsection
