@extends('components.layout-dashboard')
@section('title', 'Vergelijken')
@section('content')
<div class="section-heading"><div><h2>Vergelijk mijn ontwikkeling</h2><p>Bekijk hoe je voortgang is veranderd in de gekozen periode.</p></div></div>
<section class="panel"><h3>Periode</h3><form class="simple-form"><label for="period">Vergelijk met</label><select id="period"><option>Afgelopen 6 maanden</option><option>Afgelopen 3 maanden</option><option>Afgelopen jaar</option></select></form><div class="info-strip">Okt 2025: 64% voortgang · 16 leeruitkomsten · 208 uur → Apr 2026: 72% · 18 leeruitkomsten · 284 uur</div><p class="muted-line">Je voortgang is met 8 procentpunten gestegen in deze periode.</p></section>
<section class="panel"><h3>Leeruitkomsten</h3>@foreach ([['Vakinhoudelijk handelen',85],['Samenwerken',70],['Communiceren',60],['Professioneel handelen',45]] as [$label,$progress])<div class="outcome-row"><div><span>{{ $label }}</span><strong>{{ $progress }}%</strong></div><div class="progress-track"><i style="width:{{ $progress }}%"></i></div></div>@endforeach</section>
@endsection
