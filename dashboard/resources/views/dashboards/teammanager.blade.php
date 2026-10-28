{{-- Blade-weergave: teammanager.blade. Deze view gebruikt de gedeelde dashboardlayout waar van toepassing. --}}
@extends('components.layout-dashboard')

@section('title', 'Dashboard teamleider')
@section('welcome-title', 'Goedemiddag, ' . ($user->voornaam ?? 'teamleider') . '!')
@section('welcome-subtitle', 'Bekijk de voortgang van studenten en groepen binnen je team.')

@section('content')
    {{-- Samenvatting van de groepen en de voortgang van het team. --}}
    <div class="section-heading" id="groepen" data-dashboard-section="groepen"><div><h2>Teamoverzicht</h2><p>Voortgang en aandachtspunten binnen de aangesloten opleidingen.</p></div><span class="period-pill">Schooljaar 2025–2026</span></div>
    <section class="stat-grid" data-dashboard-section="groepen"><article class="stat-card stat-blue"><div class="stat-icon">♙</div><div><span>Studenten</span><strong>24</strong><small>In jouw team</small></div></article><article class="stat-card stat-green"><div class="stat-icon">✓</div><div><span>Gemiddelde voortgang</span><strong>68%</strong><small>Leeruitkomsten behaald</small></div></article><article class="stat-card stat-orange"><div class="stat-icon">◷</div><div><span>Gevalideerde uren</span><strong>1.842</strong><small>Dit schooljaar</small></div></article><article class="stat-card stat-purple"><div class="stat-icon">!</div><div><span>Aandacht nodig</span><strong>4 studenten</strong><small>Geen update in 30 dagen</small></div></article></section>
    {{-- Rapportages en trends kunnen los worden geopend via de zijbalk. --}}
    <div class="content-grid">
        <section class="panel" id="rapportages" data-dashboard-section="rapportages"><h3>Voortgang per opleiding</h3>@foreach ([['Software Development',74],['ICT Beheer',66],['Onderwijsassistent',62]] as [$label,$progress])<div class="outcome-row"><div><span>{{ $label }}</span><strong>{{ $progress }}%</strong></div><div class="progress-track"><i style="width:{{ $progress }}%"></i></div></div>@endforeach</section>
        <section class="panel" id="trends" data-dashboard-section="trends"><h3>Trends en aandachtspunten</h3><ul class="updates-list"><li><time>Deze periode</time><strong>Gemiddelde voortgang steeg met 6 procentpunten.</strong></li><li><time>Deze periode</time><strong>82% van de uren is gevalideerd.</strong></li><li><time>Aandachtspunt</time><strong>Communiceren blijft de laagst scorende uitkomst.</strong></li></ul></section>
    </div>
    <section class="panel" id="instellingen" data-dashboard-section="instellingen"><h3>Teaminstellingen</h3><p class="muted-line">Standaard rapportageperiode <strong>Afgelopen 6 maanden</strong></p><p class="muted-line">Team <strong>ICT · Campus Noord</strong></p></section>
@endsection
