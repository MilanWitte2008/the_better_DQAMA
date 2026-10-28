{{-- Blade-weergave: uren.blade. Deze view gebruikt de gedeelde dashboardlayout waar van toepassing. --}}
@extends('components.layout-dashboard')
@section('title', 'Uren & resultaten')
@section('content')
<div class="section-heading"><div><h2>Uren &amp; resultaten</h2><p>Bekijk je gevalideerde uren en hoe ze over de drie fases zijn verdeeld.</p></div></div>
<section class="panel"><h3>Totaal gevalideerd</h3><div class="hours-total">284 <span>uur</span></div><p class="muted-line">Schooljaar 2025–2026 <strong>✓ Gevalideerd</strong></p></section>
<section class="panel"><h3>Uren per maand</h3><div class="bar-chart tall-chart"><i style="height:43%"><b>Jan</b></i><i style="height:53%"><b>Feb</b></i><i style="height:69%"><b>Mrt</b></i><i style="height:75%"><b>Apr</b></i><i style="height:61%"><b>Mei</b></i><i style="height:81%"><b>Jun</b></i><i style="height:87%"><b>Jul</b></i><i style="height:73%"><b>Aug</b></i><i style="height:62%"><b>Sep</b></i></div></section>
<section class="panel"><h3>Uren per opdracht</h3><table class="data-table"><thead><tr><th>Opdracht</th><th>Uren</th><th>Status</th></tr></thead><tbody><tr><td>Praktijkopdracht zelfsturing</td><td>72</td><td><span class="status-pill">Behaald</span></td></tr><tr><td>Project samenwerken</td><td>48</td><td><span class="status-pill">Behaald</span></td></tr><tr><td>Stage / leerbedrijf</td><td>96</td><td><span class="status-pill pending">Lopend</span></td></tr><tr><td>Vrije opdracht</td><td>68</td><td><span class="status-pill">Behaald</span></td></tr></tbody></table></section>
@endsection
