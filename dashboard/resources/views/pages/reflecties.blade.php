@extends('components.layout-dashboard')
@section('title', 'Reflecties')
@section('content')
<div class="section-heading"><div><h2>Reflecties</h2><p>Sta stil bij wat je hebt geleerd en wat je verder wilt ontwikkelen.</p></div></div>
<div class="content-grid"><section class="panel"><h3>Nieuwe reflectie</h3><form class="simple-form" action="#" method="post"><label for="reflection">Wat heb je geleerd en wat wil je verder ontwikkelen?</label><textarea id="reflection" rows="5" placeholder="Schrijf hier je reflectie..."></textarea><button class="primary-button" type="button">Reflectie bewaren</button></form></section><section class="panel"><h3>Opgeslagen reflecties</h3><ul class="updates-list"><li><time>10 apr 2025</time><br>Ik heb geoefend met duidelijker communiceren tijdens het project.</li><li><time>5 mrt 2025</time><br>Tijdens mijn stage heb ik zelfstandiger leren werken.</li></ul></section></div>
@endsection
