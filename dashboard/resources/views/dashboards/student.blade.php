{{-- Studentdashboard met gegevens die aan het ingelogde lid gekoppeld zijn. --}}
@extends('components.layout-dashboard')

@php
    // ID van de ingelogde student.
    $memberId = $user->getKey();

    // Het schooljaar begint in augustus.
    $schoolYearStart = now()->month >= 8 ? now()->year : now()->year - 1;
    $schoolYear = $schoolYearStart . '–' . ($schoolYearStart + 1);
    $db = \Illuminate\Support\Facades\DB::connection();

    // Haal de leeruitkomsten en hun persoonlijke status op.
    $outcomes = $db->table('leeruitkomsten_has_lid as assignment')
        ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_id')
        ->where('assignment.lid_idlid', $memberId)
        ->orderByRaw("FIELD(assignment.status, 'Bezig', 'Te doen', 'Behaald')")
        ->orderBy('outcome.titel')
        ->select('outcome.titel', 'outcome.omschrijving', 'assignment.status')
        ->get();

    // Haal de urenregistraties op, nieuwste eerst.
    $hours = $db->table('urenregistratie')
        ->where('lid_id', $memberId)
        ->orderByDesc('datum')
        ->get();

    // Haal de fases van de student op.
    $phaseColumn = $db->getSchemaBuilder()->hasColumn('leeruitkomsten', 'fase_idfase1') ? 'fase_idfase1' : 'fase_idfase';
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
        'outcomes' => $outcomes,
        'totalOutcomes' => $outcomes->count(),
        'activeOutcomes' => $outcomes->whereIn('status', ['Te doen', 'Bezig'])->count(),
        'completedOutcomes' => $outcomes->where('status', 'Behaald')->count(),
        'hours' => $hours,
        'totalHours' => (float) $hours->sum(fn ($entry) => (float) $entry->uren),
        'approvedHours' => (float) $hours->where('status', 'Goedgekeurd')->sum('uren'),
        'pendingHours' => (float) $hours->where('status', 'In afwachting')->sum('uren'),
        'rejectedHours' => (float) $hours->where('status', 'Afgekeurd')->sum('uren'),
        'hoursByMonth' => $hoursByMonth,
        'phases' => $phases,
        'reflections' => $reflections,
    ];
@endphp

@section('title', 'Studentdashboard')
@section('welcome-title', 'Goedemiddag, ' . ($user->voornaam ?? 'student') . '!')
@section('welcome-subtitle', '')

@section('content')
    {{-- Persoonlijk overzicht in dezelfde dashboardindeling als de andere rollen. --}}
    <div class="section-heading" id="ontwikkeling" data-dashboard-section="ontwikkeling"><div><h2>Mijn ontwikkeling</h2><p>Bekijk je voortgang, leeruitkomsten en uren op één plek.</p></div><span class="period-pill">Schooljaar {{ $schoolYear }}</span></div>
    {{-- Toon de belangrijkste cijfers. --}}
    <section class="stat-grid" aria-label="Samenvatting" data-dashboard-section="ontwikkeling">
        <article class="stat-card stat-green">
            <div class="stat-icon">▣</div>
            <div><span>Leeruitkomsten</span><strong>{{ $studentData['totalOutcomes'] }}</strong><small>Gekoppeld aan jouw fases</small></div>
        </article>
        <article class="stat-card stat-blue">
            <div class="stat-icon">◷</div>
            <div><span>Geregistreerde uren</span><strong>{{ number_format($studentData['totalHours'], $studentData['totalHours'] == (int) $studentData['totalHours'] ? 0 : 1, ',', '.') }} uur</strong><small>{{ number_format($studentData['approvedHours'], 1, ',', '.') }} goedgekeurd · {{ number_format($studentData['pendingHours'], 1, ',', '.') }} in afwachting</small></div>
        </article>
        <article class="stat-card stat-orange">
            <div class="stat-icon">◎</div>
            <div><span>Leeruitkomsten</span><strong>{{ $studentData['activeOutcomes'] }} actief</strong><small>{{ $studentData['completedOutcomes'] }} behaald</small></div>
        </article>
        <article class="stat-card stat-purple">
            <div class="stat-icon">◉</div>
            <div><span>Voortgang fase</span><strong>{{ $studentData['phases']->last()->naam ?? 'Geen fase' }}</strong><small>{{ $studentData['phases']->count() }} fases aan jouw account gekoppeld</small></div>
        </article>
    </section>

    {{-- Toon de fases van de student. --}}
    <section class="panel" data-dashboard-section="ontwikkeling">
        <h3>Mijn fases</h3>
        @if ($studentData['phases']->isNotEmpty())
            <div class="phase-grid">
                @foreach ($studentData['phases'] as $phase)
                    @php($phaseStyle = ['phase-green', 'phase-blue', 'phase-orange'][$loop->index % 3])
                    <div class="phase {{ $phaseStyle }}">
                        <div class="ring phase-ring" style="--progress: {{ $phase->progress }}%" role="img" aria-label="{{ $phase->progress }} procent van de leeruitkomsten behaald">
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

    {{-- Toon leeruitkomsten en reflecties. --}}
    <div class="content-grid">
        <section class="panel" id="leerdoelen" data-dashboard-section="leerdoelen">
            <h3>Mijn leeruitkomsten</h3>
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

        <section class="panel" id="reflecties" data-dashboard-section="reflecties">
            <h3>Recente reflecties</h3>
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
                <p class="info-strip">Er zijn nog geen reflecties aan jouw account gekoppeld.</p>
            @endif
        </section>

        {{-- Toon de uren per maand. --}}
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

    {{-- Toon de volgende stap voor de student. --}}
    <section class="next-step" id="gesprekken" data-dashboard-section="gesprekken">
        <strong>💡 Jouw volgende stap</strong>
        @if ($studentData['outcomes']->isNotEmpty())
            <p>Bekijk je leeruitkomsten en werk verder aan: {{ $studentData['outcomes']->first()->titel ?: 'je volgende leeruitkomst' }}.</p>
        @else
            <p>Zodra er leeruitkomsten aan jouw account gekoppeld zijn, verschijnen ze hier.</p>
        @endif
        <a href="{{ route('dashboard') }}#leerdoelen" class="primary-button">Bekijk mijn leeruitkomsten →</a>
    </section>
@endsection
