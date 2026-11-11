{{-- Dit formulier is voor een nieuwe gebruiker en voor het aanpassen van een gebruiker. --}}
@extends('components.layout-dashboard')
@section('title', $member->exists ? 'Gebruiker bewerken' : 'Gebruiker toevoegen')
@section('welcome-title', 'Gebruikersbeheer')
@section('welcome-subtitle', 'Geef ieder account de juiste toegang tot het platform.')
@section('content')
<div class="admin-workspace admin-form-page">
    <a class="admin-link" href="{{ route('dashboard') }}">← Terug naar het overzicht</a>
    @if(session('success'))
        <p class="admin-alert" role="status">{{ session('success') }}</p>
    @endif
    <section class="panel" style="margin-top:20px">
        <div class="admin-panel-heading">
            <div>
                <span class="admin-eyebrow">ACCOUNTBEHEER</span>
                <h2>
                    {{ $member->exists ? 'Gebruiker bewerken' : 'Nieuwe gebruiker' }}
                </h2>
                <p>
                    Velden met een * zijn verplicht.
                </p>
            </div>
        </div>
        {{-- Toon de fouten als het formulier niet goed is ingevuld. --}}
        @if($errors->any())
            <div class="admin-alert admin-error" role="alert">
                <strong>Opslaan is niet gelukt.</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{-- Kies de juiste actie: toevoegen of aanpassen. --}}
        <form
                class="simple-form"
                method="POST"
                action="{{ $member->exists ? route('beheer.members.update', $member) : route('beheer.members.store') }}">
            {{-- Dit token helpt tegen het versturen van een formulier vanaf een andere website. --}}
            @csrf
            {{-- Bij aanpassen sturen we een PUT-verzoek naar Laravel. --}}
            @if($member->exists) @method('PUT') @endif
            <div class="admin-form-grid">
                {{-- Maak de drie velden. old() bewaart de invoer na een fout. --}}
                @foreach(['voornaam' => 'Voornaam', 'achternaam' => 'Achternaam', 'email' => 'E-mailadres'] as $field => $label)
                    <label>{{ $label }} *<input
                        name="{{ $field }}"
                        type="{{ $field === 'email' ? 'email' : 'text' }}"
                        value="{{ old($field, $member->$field) }}"
                        maxlength="{{ $field === 'email' ? 100 : 50 }}" required @if($errors->has($field)) aria-invalid="true" @endif></label>
                @endforeach
                {{-- Kies een rol uit de database. De huidige keuze blijft geselecteerd. --}}
                <label>Rol *<select name="rol_idrol" required><option value="">Selecteer een rol</option>@foreach($roles as $role)<option value="{{ $role->idrol }}" @selected(old('rol_idrol', $member->rol_idrol) == $role->idrol)>{{ $role->displayName() }}</option>@endforeach</select></label>
                {{-- Kies of het account actief of inactief is. --}}
                <label>Status *<select name="status"><option value="actief" @selected(old('status', $member->status ?? 'actief') === 'actief')>Actief</option><option value="inactief" @selected(old('status', $member->status) === 'inactief')>Inactief</option></select></label>
            </div>
            {{-- Toon extra uitleg als de beheerder het eigen account bewerkt. --}}
            @if($hasStudentProfiles)
                <fieldset class="admin-password">
                    <legend>Studentgegevens</legend>
                    <p>Voor accounts met een studentprofiel of de rol Student.</p>
                    <div class="admin-form-grid">
                        @foreach(['studentnummer' => ['Studentnummer', 20], 'klas' => ['Klas', 20], 'opleiding' => ['Opleiding', 50]] as $field => [$label, $max])
                            <label>{{ $label }}<input name="{{ $field }}" value="{{ old($field, $studentProfile?->$field) }}" maxlength="{{ $max }}" @if($errors->has($field)) aria-invalid="true" @endif></label>
                        @endforeach
                    </div>
                </fieldset>
            @endif
            @if($member->is(auth()->user()))
                <p class="info-strip">
                    Dit is je eigen account. Je rol en actieve status moeten behouden blijven.
                </p>
            @endif
            {{-- Bij een nieuwe gebruiker is een wachtwoord verplicht. Bij aanpassen mag het leeg blijven. --}}
            <fieldset class="admin-password">
                <legend>
                    {{ $member->exists ? 'Wachtwoord wijzigen (optioneel)' : 'Wachtwoord instellen' }}
                </legend>
                <p>
                    Minimaal 12 tekens. {{ $member->exists ? 'Laat beide velden leeg om het huidige wachtwoord te behouden.' : '' }}
                </p>
                <div class="admin-form-grid">
                    <label>Wachtwoord {{ $member->exists ? '' : '*' }}<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="128" @required(!$member->exists)></label><label>Wachtwoord herhalen {{ $member->exists ? '' : '*' }}<input
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        minlength="12"
                        maxlength="128" @required(!$member->exists)></label>
                </div>
            </fieldset>
            {{-- Opslaan verstuurt het formulier. Annuleren gaat terug zonder op te slaan. --}}
            <div class="admin-form-actions">
                <button class="admin-button" type="submit">{{ $member->exists ? 'Wijzigingen opslaan' : 'Gebruiker aanmaken' }}</button><a class="admin-link" href="{{ route('dashboard') }}">Annuleren</a>
            </div>
        </form>
    </section>
    @if($member->exists && $member->dashboardRoleKey() === 'student')
        <section class="panel" style="margin-top:24px">
            <h2>Fases beheren</h2>
            <p>Koppel een bestaande fase of maak een nieuwe fase voor deze student. Voortgang wordt berekend uit behaalde leeruitkomsten.</p>
            @if($assignedPhases->isNotEmpty())
                <p>Gekoppeld: {{ $assignedPhases->pluck('naam')->implode(', ') }}</p>
            @endif
            <form class="simple-form" method="POST" action="{{ route('beheer.members.phases.store', $member) }}">
                @csrf
                <div class="admin-form-grid">
                    <label>Bestaande fase<select name="phase_id"><option value="">Kies een fase of maak een nieuwe</option>@foreach($availablePhases as $phase)<option value="{{ $phase->idfase }}" @selected(old('phase_id') == $phase->idfase)>{{ $phase->naam }}</option>@endforeach</select></label>
                    <label>Of: nieuwe fasenaam<input name="phase_name" maxlength="50" value="{{ old('phase_name') }}" placeholder="Bijvoorbeeld: Oriëntatie"></label>
                </div>
                <button class="admin-button" type="submit">Fase koppelen</button>
            </form>
        </section>
        <section class="panel" style="margin-top:24px">
            <h2>Uren beheren</h2>
            <p>Registreer uren voor {{ $member->voornaam }}. Alleen goedgekeurde uren verschijnen in de maandgrafiek.</p>
            @foreach($studentHours->prepend(null) as $entry)
                <details class="admin-password" @if(!$entry || old('entry_id') == $entry->idurenregistratie) open @endif>
                    <summary>{{ $entry ? $entry->datum.' · '.$entry->uren.' uur · '.$entry->status : 'Nieuwe urenregistratie' }}</summary>
                    <form class="simple-form" method="POST" action="{{ route('beheer.members.hours.save', $member) }}">
                        @csrf
                        <input type="hidden" name="entry_id" value="{{ $entry?->idurenregistratie }}">
                        @php($restore = (string) old('entry_id') === (string) $entry?->idurenregistratie)
                        <div class="admin-form-grid">
                            <label>Datum<input type="date" name="datum" max="{{ today()->toDateString() }}" value="{{ $restore ? old('datum', $entry?->datum ?? today()->toDateString()) : ($entry?->datum ?? today()->toDateString()) }}" required></label>
                            <label>Uren<input type="number" name="uren" min="0.01" max="24" step="0.01" value="{{ $restore ? old('uren', $entry?->uren) : $entry?->uren }}" required></label>
                            <label>Status<select name="status">@foreach(['In afwachting', 'Goedgekeurd', 'Afgekeurd'] as $status)<option @selected(($restore ? old('status', $entry?->status ?? 'In afwachting') : ($entry?->status ?? 'In afwachting')) === $status)>{{ $status }}</option>@endforeach</select></label>
                            <label>Omschrijving<input name="omschrijving" maxlength="255" value="{{ $restore ? old('omschrijving', $entry?->omschrijving) : $entry?->omschrijving }}" required></label>
                        </div>
                        <button class="admin-button" type="submit">{{ $entry ? 'Uren bijwerken' : 'Uren toevoegen' }}</button>
                    </form>
                </details>
            @endforeach
        </section>
    @endif
</div>
@endsection
