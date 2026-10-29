{{-- Studentdashboard met gegevens die aan het ingelogde lid gekoppeld zijn. --}}
@extends('components.layout-dashboard')

@php
    // Bepaal welke student is ingelogd en welk schooljaar wordt getoond.
    $memberId = $user->getKey();
    $schoolYearStart = now()->month >= 8 ? now()->year : now()->year - 1;
    $schoolYear = $schoolYearStart . '–' . ($schoolYearStart + 1);

    // Haal de leerdoelen en uren van alleen deze student op.
    $goals = \Illuminate\Support\Facades\DB::table('doelen')
        ->leftJoin('fase', 'fase.idfase', '=', 'doelen.fase_idfase')
        ->where('lid_idlid', $memberId)
        ->select('doelen.*', 'fase.naam as fase_naam')
        ->orderByRaw("FIELD(doelen.status, 'Bezig', 'Te doen', 'Behaald')")
        ->orderBy('doelen.einddatum')
        ->get();

    $hours = \Illuminate\Support\Facades\DB::table('urenregistratie')
        ->where('lid_id', $memberId)
        ->orderByDesc('datum')
        ->get();

    // Haal de fases en leeruitkomsten op die aan deze student gekoppeld zijn.
    $phaseRows = \Illuminate\Support\Facades\DB::table('fase_has_lid as assignment')
        ->join('fase', 'fase.idfase', '=', 'assignment.fase_idfase')
        ->leftJoin('leeruitkomsten', 'leeruitkomsten.fase_idfase1', '=', 'fase.idfase')
        ->where('assignment.lid_idlid', $memberId)
        ->select([
            'fase.idfase',
            'fase.naam as fase_naam',
            'fase.beschrijving as fase_beschrijving',
            'fase.volgorde',
            'leeruitkomsten.leeruitkomsten_id',
            'leeruitkomsten.code',
            'leeruitkomsten.titel',
            'leeruitkomsten.omschrijving',
        ])
        ->orderBy('fase.volgorde')
        ->orderBy('leeruitkomsten.titel')
        ->get();

    // Groepeer de leeruitkomsten per fase.
    $phases = $phaseRows
        ->groupBy('idfase')
        ->map(fn ($rows) => (object) [
            'idfase' => $rows->first()->idfase,
            'naam' => $rows->first()->fase_naam,
            'beschrijving' => $rows->first()->fase_beschrijving,
            'leeruitkomsten' => $rows
                ->filter(fn ($row) => $row->leeruitkomsten_id !== null)
                ->unique('leeruitkomsten_id')
                ->values(),
        ])
        ->values();

    $outcomes = $phases->flatMap(fn ($phase) => $phase->leeruitkomsten)
        ->unique('leeruitkomsten_id')
        ->values();

    // Bereken per fase hoeveel doelen klaar, bezig of te doen zijn.
    $phaseGoalCards = $phases->map(function ($phase) use ($goals) {
        $phaseGoals = $goals->where('fase_idfase', $phase->idfase);
        $total = $phaseGoals->count();
        $ready = $phaseGoals->where('status', 'Behaald')->count();
        $inProgress = $phaseGoals->where('status', 'Bezig')->count();
        $toDo = $phaseGoals->where('status', 'Te doen')->count();

        $readyEnd = $total > 0 ? (int) round(($ready / $total) * 100) : 0;
        $inProgressEnd = $total > 0 ? (int) round((($ready + $inProgress) / $total) * 100) : 0;
        $gradient = $total > 0
            ? "conic-gradient(#0aa184 0 {$readyEnd}%, #1688ed {$readyEnd}% {$inProgressEnd}%, #e99a22 {$inProgressEnd}% 100%)"
            : 'conic-gradient(#e0eaf0 0 100%)';

        return (object) [
            'naam' => $phase->naam,
            'totaal' => $total,
            'klaar' => $ready,
            'bezig' => $inProgress,
            'te_doen' => $toDo,
            'gradient' => $gradient,
        ];
    });

    // Haal de vijf meest recente reflecties van deze student op.
    $reflections = \Illuminate\Support\Facades\DB::table('reflectie')
        ->where('fase_has_lid_lid_idlid', $memberId)
        ->orderByDesc('datum')
        ->orderByDesc('idreflectie')
        ->limit(5)
        ->get();

    // Tel de goedgekeurde uren per maand voor de grafiek.
    $monthStart = now()->startOfMonth()->subMonths(5);
    $hoursByMonth = collect(range(0, 5))->map(function (int $offset) use ($monthStart, $hours) {
        $month = $monthStart->copy()->addMonths($offset);
        $monthHours = $hours
            ->filter(fn ($entry) => $entry->status === 'Goedgekeurd'
                && \Illuminate\Support\Carbon::parse($entry->datum)->format('Y-m') === $month->format('Y-m'))
            ->sum(fn ($entry) => (float) $entry->uren);

        return [
            'label' => $month->locale('nl')->translatedFormat('M'),
            'hours' => $monthHours,
        ];
    });

    $maxMonthHours = max(1, (float) $hoursByMonth->max('hours'));
    $hoursByMonth = $hoursByMonth->map(fn (array $month) => $month + [
        'percentage' => $month['hours'] > 0
            ? max(3, (int) round(($month['hours'] / $maxMonthHours) * 100))
            : 0,
    ]);

    // Geef alle berekende gegevens door aan de dashboardweergave.
    $studentData = [
        'goals' => $goals,
        'activeGoals' => $goals->whereIn('status', ['Te doen', 'Bezig'])->count(),
        'completedGoals' => $goals->where('status', 'Behaald')->count(),
        'hours' => $hours,
        'totalHours' => (float) $hours->sum(fn ($entry) => (float) $entry->uren),
        'approvedHours' => (float) $hours->where('status', 'Goedgekeurd')->sum('uren'),
        'pendingHours' => (float) $hours->where('status', 'In afwachting')->sum('uren'),
        'rejectedHours' => (float) $hours->where('status', 'Afgekeurd')->sum('uren'),
        'hoursByMonth' => $hoursByMonth,
        'phaseGoalCards' => $phaseGoalCards,
        'phases' => $phases,
        'outcomes' => $outcomes,
        'reflections' => $reflections,
    ];
@endphp

@section('title', 'Studentdashboard')
@section('welcome-title', 'Goedemiddag, ' . ($user->voornaam ?? 'student') . '!')
@section('welcome-subtitle', '')

@section('content')
    {{-- Persoonlijk overzicht in dezelfde dashboardindeling als de andere rollen. --}}
    <div class="section-heading" id="ontwikkeling" data-dashboard-section="ontwikkeling"><div><h2>Mijn ontwikkeling</h2><p>Bekijk je voortgang, leeruitkomsten en uren op één plek.</p></div><span class="period-pill">Schooljaar {{ $schoolYear }}</span></div>
    <section class="stat-grid" aria-label="Samenvatting" data-dashboard-section="ontwikkeling">
        <article class="stat-card stat-green">
            <div class="stat-icon">▣</div>
            <div><span>Leeruitkomsten</span><strong>{{ $studentData['outcomes']->count() }}</strong><small>Gekoppeld aan jouw fases</small></div>
        </article>
        <article class="stat-card stat-blue">
            <div class="stat-icon">◷</div>
            <div><span>Geregistreerde uren</span><strong>{{ number_format($studentData['totalHours'], $studentData['totalHours'] == (int) $studentData['totalHours'] ? 0 : 1, ',', '.') }} uur</strong><small>{{ number_format($studentData['approvedHours'], 1, ',', '.') }} goedgekeurd · {{ number_format($studentData['pendingHours'], 1, ',', '.') }} in afwachting</small></div>
        </article>
        <article class="stat-card stat-orange">
            <div class="stat-icon">◎</div>
            <div><span>Leerdoelen</span><strong>{{ $studentData['activeGoals'] }} actief</strong><small>{{ $studentData['completedGoals'] }} behaald</small></div>
        </article>
        <article class="stat-card stat-purple">
            <div class="stat-icon">◉</div>
            <div><span>Voortgang fase</span><strong>{{ $studentData['phases']->last()->naam ?? 'Geen fase' }}</strong><small>{{ $studentData['phases']->count() }} fases aan jouw account gekoppeld</small></div>
        </article>
    </section>

    <section class="panel" data-dashboard-section="ontwikkeling">
        <h3>Leerdoelen per fase</h3>
        @if ($studentData['phaseGoalCards']->isNotEmpty())
            <div class="phase-grid">
                @foreach ($studentData['phaseGoalCards'] as $phase)
                    @php($phaseStyle = ['phase-green', 'phase-blue', 'phase-orange'][$loop->index % 3])
                    <div class="phase {{ $phaseStyle }}">
                        <div class="ring" style="background: {{ $phase->gradient }}" aria-label="{{ $phase->totaal }} doelen: {{ $phase->klaar }} klaar, {{ $phase->bezig }} bezig, {{ $phase->te_doen }} te doen">
                            <span>{{ $phase->totaal }}</span>
                        </div>
                        <strong>{{ $phase->naam }}</strong>
                        <small>{{ $phase->klaar }} klaar · {{ $phase->bezig }} bezig · {{ $phase->te_doen }} te doen</small>
                    </div>
                @endforeach
            </div>
        @else
            <p class="info-strip">Er zijn nog geen fases aan jouw account gekoppeld.</p>
        @endif
    </section>

    <div class="content-grid">
        <section class="panel" id="leerdoelen" data-dashboard-section="leerdoelen">
            <h3>Mijn leerdoelen</h3>
            @if ($studentData['goals']->isNotEmpty())
                <ul class="goal-list">
                    @foreach ($studentData['goals'] as $goal)
                        <li>
                            <span>
                                <strong>{{ $goal->titel ?: 'Leerdoel zonder titel' }}</strong>
                                @if ($goal->omschrijving)
                                    <small class="widget-detail">{{ $goal->omschrijving }}</small>
                                @endif
                            </span>
                            <span class="goal-tag">{{ $goal->status ?: 'Onbekend' }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="info-strip">Er zijn nog geen leerdoelen aan jouw account gekoppeld.</p>
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

        <section class="panel" id="uren" data-dashboard-section="uren">
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

        <section class="panel" id="vergelijken" data-dashboard-section="vergelijken">
            <h3>Mijn leeruitkomsten</h3>
            @if ($studentData['outcomes']->isNotEmpty())
                <ul class="updates-list">
                    @foreach ($studentData['outcomes'] as $outcome)
                        <li>
                            <strong>{{ $outcome->titel ?: $outcome->code }}</strong>
                            @if ($outcome->omschrijving)
                                <small class="widget-detail">{{ $outcome->omschrijving }}</small>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="info-strip">Er zijn nog geen leeruitkomsten aan jouw fases gekoppeld.</p>
            @endif
        </section>
    </div>

    <section class="next-step" id="gesprekken" data-dashboard-section="gesprekken">
        <strong>💡 Jouw volgende stap</strong>
        @if ($studentData['goals']->isNotEmpty())
            <p>Bekijk je leerdoelen en werk verder aan: {{ $studentData['goals']->first()->titel ?: 'je volgende leerdoel' }}.</p>
        @else
            <p>Zodra er leerdoelen aan jouw account gekoppeld zijn, verschijnen ze hier.</p>
        @endif
        <a href="{{ route('dashboard') }}#leerdoelen" class="primary-button">Bekijk mijn leerdoelen →</a>
    </section>
@endsection
