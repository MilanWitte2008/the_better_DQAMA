{{-- Blade-weergave: docent.blade. Deze view gebruikt de gedeelde dashboardlayout waar van toepassing. --}}
@extends('components.layout-dashboard')

@section('title', 'Docentdashboard')
@section('welcome-title', 'Goedemiddag, ' . ($user->voornaam ?? 'docent') . '!')
@section('welcome-subtitle', 'Bekijk de ontwikkeling van je studenten en welke voortgang aandacht vraagt.')

@section('content')
    <div class="section-heading" id="studenten"><div><h2>Studenten</h2><p>Open een studentoverzicht en volg de voortgang van je studenten.</p></div></div>
    <section class="stat-grid"><article class="stat-card stat-blue"><div class="stat-icon">♙</div><div><span>Studenten</span><strong>5</strong><small>Onder jouw begeleiding</small></div></article><article class="stat-card stat-green"><div class="stat-icon">☷</div><div><span>Coachgesprekken</span><strong>3 gepland</strong><small>Deze maand</small></div></article><article class="stat-card stat-orange"><div class="stat-icon">▤</div><div><span>Notities</span><strong>8</strong><small>Dit schooljaar</small></div></article><article class="stat-card stat-purple"><div class="stat-icon">!</div><div><span>Aandacht nodig</span><strong>2 studenten</strong><small>Geen recente update</small></div></article></section>
    <section class="panel"><h3>Studentvoortgang</h3>@foreach ([['Sanne de Vries','MBO Verpleegkunde · Jaar 2',72],['Jordi Bakker','MBO Verpleegkunde · Jaar 2',58],['Lisa van Dijk','MBO Verpleegkunde · Jaar 2',65],['Thomas de Groot','MBO Verpleegkunde · Jaar 2',48],['Emma Janssen','MBO Onderwijsassistent · Jaar 2',80]] as [$name,$course,$progress])<div class="student-row"><span class="avatar">{{ collect(explode(' ', $name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span><span class="student-info"><strong>{{ $name }}</strong><small>{{ $course }}</small></span><span class="progress-track"><i style="width:{{ $progress }}%"></i></span><strong class="student-percent">{{ $progress }}%</strong></div>@endforeach</section>
    <div class="content-grid">
        <section class="panel" id="gesprekken"><h3>Coachgesprekken</h3><ul class="updates-list"><li><time>10 apr 2025</time><br><strong>Leerdoel communicatie · Sanne de Vries</strong><small class="widget-detail">Oefensituaties besproken om duidelijker te communiceren.</small></li><li><time>5 mrt 2025</time><br><strong>Praktijkperiode · Jordi Bakker</strong><small class="widget-detail">Voortgang en zelfstandigheid doorgenomen.</small></li></ul></section>
        <section class="panel" id="notities"><h3>Notitie vastleggen</h3><form class="simple-form"><label for="student-note">Notitie voor student</label><textarea id="student-note" rows="4" placeholder="Schrijf een coachnotitie"></textarea><button class="primary-button" type="button">Notitie bewaren</button></form></section>
        <section class="panel" id="rapportages"><h3>Leeruitkomsten team</h3>@foreach ([['Vakinhoudelijk handelen',85],['Samenwerken',70],['Communiceren',60],['Professioneel handelen',45]] as [$label,$progress])<div class="outcome-row"><div><span>{{ $label }}</span><strong>{{ $progress }}%</strong></div><div class="progress-track"><i style="width:{{ $progress }}%"></i></div></div>@endforeach</section>
    </div>
@endsection
