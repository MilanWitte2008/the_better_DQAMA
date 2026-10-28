@extends('components.layout-dashboard')
@section('title', 'Coachgesprekken')
@section('content')
<div class="section-heading"><div><h2>Coachgesprekken</h2><p>Bekijk eerdere gesprekken of leg een nieuw gesprek vast.</p></div></div>
<section class="panel"><h3>Eerdere gesprekken</h3><ul class="updates-list"><li><time>10 apr 2025</time><br><strong>Leerdoel communicatie</strong><br>Besproken welke oefensituaties helpen om duidelijker te communiceren.</li><li><time>5 mrt 2025</time><br><strong>Praktijkperiode</strong><br>Voortgang en zelfstandigheid tijdens de stage doorgenomen.</li></ul></section>
<section class="panel"><h3>Gesprek toevoegen</h3><form class="simple-form" action="#" method="post"><label for="meeting-date">Datum gesprek</label><input id="meeting-date" type="date"><label for="subject">Onderwerp</label><input id="subject" type="text" placeholder="Bijvoorbeeld: voortgang leerdoelen"><button class="primary-button" type="button">Gesprek opslaan</button></form></section>
@endsection
