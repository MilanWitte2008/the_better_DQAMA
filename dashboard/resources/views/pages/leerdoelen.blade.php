@extends('components.layout-dashboard')
@section('title', 'Mijn leerdoelen')
@section('content')
<div class="section-heading"><div><h2>Mijn leerdoelen</h2><p>Houd je persoonlijke doelen bij en werk stap voor stap aan je ontwikkeling.</p></div></div>
<section class="panel"><h3>Actieve leerdoelen</h3><ul class="goal-list"><li>Verdiepen in projectmatig werken <span class="goal-tag">In ontwikkeling</span></li><li>Reflectie en communicatie verbeteren <span class="goal-tag">In ontwikkeling</span></li><li>Meer zelfstandig leren werken <span class="goal-tag">Nieuw</span></li></ul></section>
<section class="panel"><h3>Nieuw leerdoel</h3><form class="simple-form" action="#" method="post"><label for="goal">Waar wil je aan werken?</label><input id="goal" type="text" placeholder="Bijvoorbeeld: duidelijker presenteren"><button class="primary-button" type="button">Leerdoel toevoegen</button></form></section>
@endsection
