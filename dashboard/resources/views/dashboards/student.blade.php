{{-- Blade-weergave: student.blade. Deze view gebruikt de gedeelde dashboardlayout waar van toepassing. --}}
@extends('components.layout-dashboard')

@section('title', 'Studentdashboard')
@section('welcome-title', 'Goedemiddag, ' . ($user->voornaam ?? 'student') . '!')
@section('welcome-subtitle', 'Je totale voortgang is 72%. Bekijk jouw ontwikkeling, leeruitkomsten en uren.')

@section('content')
    <div class="section-heading" id="ontwikkeling"><div><h2>Mijn ontwikkeling</h2><p>Bekijk je voortgang, leeruitkomsten en uren op één plek.</p></div><span class="period-pill">Schooljaar 2025–2026</span></div>
    <section class="stat-grid" aria-label="Samenvatting">
        <article class="stat-card stat-green"><div class="stat-icon">▣</div><div><span>Leeruitkomsten</span><strong>18 / 24</strong><small>Behaald · totale voortgang 72%</small></div></article>
        <article class="stat-card stat-blue"><div class="stat-icon">◷</div><div><span>Geregistreerde uren</span><strong>284 uur</strong><small>Dit schooljaar</small></div></article>
        <article class="stat-card stat-orange"><div class="stat-icon">◎</div><div><span>Leerdoelen</span><strong>3 actief</strong><small>Persoonlijke doelen</small></div></article>
        <article class="stat-card stat-purple"><div class="stat-icon">◉</div><div><span>Voortgang fase</span><strong>Verdiepingsfase</strong><small>52% voltooid</small></div></article>
    </section>
    <section class="panel"><h3>Ontwikkeling per fase</h3><div class="phase-grid"><div class="phase phase-green"><div class="ring ring-green"><span>80%</span></div><strong>Oriëntatie</strong></div><div class="phase phase-blue"><div class="ring ring-blue"><span>52%</span></div><strong>Verdieping</strong></div><div class="phase phase-orange"><div class="ring ring-orange"><span>30%</span></div><strong>Afronding</strong></div></div></section>
    <div class="content-grid">
        <section class="panel" id="leerdoelen"><h3>Voortgang leeruitkomsten</h3>@foreach ([['Vakinhoudelijk handelen',85],['Samenwerken',70],['Communiceren',60],['Professioneel handelen',45],['Leren en ontwikkelen',80]] as [$label,$progress])<div class="outcome-row"><div><span>{{ $label }}</span><strong>{{ $progress }}%</strong></div><div class="progress-track"><i style="width:{{ $progress }}%"></i></div></div>@endforeach</section>
        <section class="panel" id="reflecties"><h3>Recente updates</h3><ul class="updates-list"><li><time>12 apr</time>Uren vanuit opdracht verwerkt</li><li><time>10 apr</time>Nieuwe reflectie opgeslagen</li><li><time>8 apr</time>Coachgesprek toegevoegd</li></ul></section>
        <section class="panel" id="uren"><h3>Uren &amp; resultaten</h3><div class="hours-total">284 <span>uur</span></div><div class="bar-chart"><i style="height:43%"><b>Jan</b></i><i style="height:53%"><b>Feb</b></i><i style="height:69%"><b>Mrt</b></i><i style="height:75%"><b>Apr</b></i><i style="height:61%"><b>Mei</b></i><i style="height:81%"><b>Jun</b></i><i style="height:87%"><b>Jul</b></i><i style="height:73%"><b>Aug</b></i><i style="height:62%"><b>Sep</b></i></div><p class="muted-line">Praktijkopdracht zelfsturing <strong>72 u · Behaald</strong></p></section>
        <section class="panel" id="vergelijken"><h3>Mijn leerdoelen</h3><ul class="goal-list"><li>Verdiepen in projectmatig werken</li><li>Reflectie en communicatie verbeteren</li><li>Meer zelfstandig leren werken</li></ul><a class="text-link" href="{{ route('vergelijken') }}">Vergelijk mijn ontwikkeling →</a></section>
    </div>
    <section class="next-step" id="gesprekken"><strong>💡 Jouw volgende stap</strong><p>Je hebt al een sterke basis in samenwerken. Bekijk welke leeractiviteiten kunnen bijdragen aan je volgende leerdoel.</p><a href="{{ route('gesprekken') }}" class="primary-button">Bekijk gesprekken →</a></section>
@endsection
