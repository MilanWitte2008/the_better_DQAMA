{{-- Blade-weergave: applicatiebeheerder.blade. Deze view gebruikt de gedeelde dashboardlayout waar van toepassing. --}}
@extends('components.layout-dashboard')

@section('title', 'Dashboard applicatiebeheerder')
@section('welcome-title', 'Beheeromgeving')
@section('welcome-subtitle', 'Beheer gebruikers, rollen en de instellingen van het ontwikkelplatform.')

@section('content')
    {{-- Samenvatting van gebruikers, rollen, scholen en de systeemstatus. --}}
    <div class="section-heading" data-dashboard-section="gebruikers"><div><h2>Applicatiebeheer</h2><p>Overzicht van gebruikers en systeemonderdelen.</p></div></div>
    <section class="stat-grid" data-dashboard-section="gebruikers"><article class="stat-card stat-blue"><div class="stat-icon">♙</div><div><span>Gebruikers</span><strong>6</strong><small>Actieve accounts</small></div></article><article class="stat-card stat-green"><div class="stat-icon">⚿</div><div><span>Rollen</span><strong>4</strong><small>In gebruik</small></div></article><article class="stat-card stat-orange"><div class="stat-icon">▦</div><div><span>Scholen</span><strong>2</strong><small>Aangesloten locaties</small></div></article><article class="stat-card stat-purple"><div class="stat-icon">✓</div><div><span>Systeemstatus</span><strong>Online</strong><small>Applicatie beschikbaar</small></div></article></section>
    <section class="panel" id="gebruikers" data-dashboard-section="gebruikers"><h3>Gebruikers</h3><div class="table-scroll"><table class="data-table"><thead><tr><th>Naam</th><th>E-mail</th><th>Rol</th><th>Status</th></tr></thead><tbody><tr><td>Emma Jansen</td><td>emma@example.com</td><td>Student</td><td><span class="status-pill">Actief</span></td></tr><tr><td>Sophie Visser</td><td>sophie@example.com</td><td>Docent</td><td><span class="status-pill">Actief</span></td></tr><tr><td>Iris Beheer</td><td>iris@example.com</td><td>Applicatiebeheerder</td><td><span class="status-pill">Actief</span></td></tr><tr><td>Milan Manager</td><td>milan@example.com</td><td>Teamleider</td><td><span class="status-pill">Actief</span></td></tr></tbody></table></div></section>
    {{-- Rollen en scholen staan in aparte onderdelen van dit dashboard. --}}
    <div class="content-grid">
        <section class="panel" id="rollen" data-dashboard-section="rollen"><h3>Rollen &amp; rechten</h3><ul class="goal-list"><li>Student <span class="goal-tag">Eigen voortgang</span></li><li>Docent <span class="goal-tag">Toegewezen studenten</span></li><li>Applicatiebeheerder <span class="goal-tag">Systeembeheer</span></li><li>Teamleider <span class="goal-tag">Teamoverzicht</span></li></ul></section>
        <section class="panel" id="scholen" data-dashboard-section="scholen"><h3>Scholen</h3><ul class="updates-list"><li><strong>Campus Noord</strong><small class="widget-detail">Stationsstraat 10</small></li><li><strong>College Zuid</strong><small class="widget-detail">Schoollaan 25</small></li></ul></section>
    </div>
    <section class="panel" id="systeem" data-dashboard-section="systeem"><h3>Systeemstatus</h3><p class="info-strip">Applicatie beschikbaar · Databaseverbinding wordt gecontroleerd door de server.</p></section>
@endsection
