{{-- Studentdashboard met gegevens die aan het ingelogde lid gekoppeld zijn. --}}
@extends('components.layout-dashboard')

@php
    $memberId = $user->getKey();
    $schoolYearStart = now()->month >= 8 ? now()->year : now()->year - 1;
    $schoolYear = $schoolYearStart . '–' . ($schoolYearStart + 1);
    $db = \Illuminate\Support\Facades\DB::connection();

    $goals = $db->table('doelen')
        ->where('lid_idlid', $memberId)
        ->orderByRaw("FIELD(status, 'Bezig', 'Te doen', 'Behaald')")
        ->orderBy('einddatum')
        ->get();

    $hours = $db->table('urenregistratie')
        ->where('lid_id', $memberId)
        ->orderByDesc('datum')
        ->get();

    // Haal de fases op die aan deze student gekoppeld zijn.
    $phases = $db->table('fase_has_lid as assignment')
        ->join('fase', 'fase.idfase', '=', 'assignment.fase_idfase')
        ->where('assignment.lid_idlid', $memberId)
        ->select('fase.idfase', 'fase.naam', 'fase.beschrijving', 'fase.volgorde')
        ->orderBy('fase.volgorde')
        ->get();

    $reflections = $db->table('reflectie')
        ->where('fase_has_lid_lid_idlid', $memberId)
        ->orderByDesc('datum')
        ->orderByDesc('idreflectie')
        ->limit(5)
        ->get();

    $monthStart = now()->startOfMonth()->subMonths(5);
    $monthlyHours = $db->table('urenregistratie')
        ->where('lid_id', $memberId)
        ->where('status', 'Goedgekeurd')
        ->whereBetween('datum', [$monthStart->toDateString(), now()->toDateString()])
        ->selectRaw("DATE_FORMAT(datum, '%Y-%m') AS maand, SUM(uren) AS totaal")
        ->groupByRaw("DATE_FORMAT(datum, '%Y-%m')")
        ->pluck('totaal', 'maand');

    $hoursByMonth = collect(range(0, 5))->map(function ($offset) use ($monthStart, $monthlyHours) {
        $month = $monthStart->copy()->addMonths($offset);
        $key = $month->format('Y-m');

        return [
            'label' => $month->locale('nl')->translatedFormat('M'),
            'hours' => (float) $monthlyHours->get($key, 0),
        ];
    });

    $maxMonthHours = max(1, (float) $hoursByMonth->max('hours'));
    $hoursByMonth = $hoursByMonth->map(function (array $month) use ($maxMonthHours) {
        $month['percentage'] = $month['hours'] > 0
            ? max(3, (int) round(($month['hours'] / $maxMonthHours) * 100))
            : 0;
        return $month;
    });

    $studentData = [
        'goals' => $goals,
        'totalGoals' => $goals->count(),
        'activeGoals' => $goals->whereIn('status', ['Te doen', 'Bezig'])->count(),
        'completedGoals' => $goals->where('status', 'Behaald')->count(),
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
    {{-- Toon de belangrijkste cijfers van deze student. --}}
    <section class="stat-grid" aria-label="Samenvatting" data-dashboard-section="ontwikkeling">
        <article class="stat-card stat-green">
            <div class="stat-icon">▣</div>
            <div><span>Leerdoelen</span><strong>{{ $studentData['totalGoals'] }}</strong><small>Gekoppeld aan jouw fases</small></div>
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

    {{-- Toon de fases die aan de student gekoppeld zijn. --}}
    <section class="panel" data-dashboard-section="ontwikkeling">
        <h3>Mijn fases</h3>
        @if ($studentData['phases']->isNotEmpty())
            <div class="phase-grid">
                @foreach ($studentData['phases'] as $phase)
                    @php($phaseStyle = ['phase-green', 'phase-blue', 'phase-orange'][$loop->index % 3])
                    <div class="phase {{ $phaseStyle }}">
                        <strong>{{ $phase->naam }}</strong>
                        <small>{{ $phase->beschrijving ?: 'Fase ' . $phase->volgorde }}</small>
                    </div>
                @endforeach
            </div>
        @else
            <p class="info-strip">Er zijn nog geen fases aan jouw account gekoppeld.</p>
        @endif
    </section>

    {{-- Toon de persoonlijke doelen en recente reflecties. --}}
    <div class="content-grid">
        <section class="panel" id="leerdoelen" data-dashboard-section="leerdoelen">
            <h3>Mijn leerdoelen</h3>
            @if ($studentData['goals']->isNotEmpty())
                <div class="scroll-field">
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
                </div>
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

        {{-- Toon het aantal uren en de goedgekeurde uren per maand. --}}
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

    {{-- Geef een volgende stap op basis van het eerste leerdoel. --}}
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
