{{-- Dit formulier is voor een nieuwe gebruiker en voor het aanpassen van een gebruiker. --}}
@extends('components.layout-dashboard')
@section('title', $member->exists ? 'Gebruiker bewerken' : 'Gebruiker toevoegen')
@section('welcome-title', 'Gebruikersbeheer')
@section('welcome-subtitle', 'Geef ieder account de juiste toegang tot het platform.')
@section('content')
<div class="admin-workspace admin-form-page">
    <a class="admin-link" href="{{ route('dashboard') }}">← Terug naar het overzicht</a>
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
                <label>Rol *<select name="rol_idrol" required><option value="">Selecteer een rol</option>@foreach($roles as $role)<option value="{{ $role->idrol }}" @selected(old('rol_idrol', $member->rol_idrol) == $role->idrol)>{{ $role->naam }}</option>@endforeach</select></label>
                {{-- Een school kiezen is niet verplicht. --}}
                <label>School<select name="school_idschool"><option value="">Niet toegewezen</option>@foreach($schools as $school)<option value="{{ $school->idschool }}" @selected(old('school_idschool', $member->school_idschool) == $school->idschool)>{{ $school->naam }}</option>@endforeach</select></label>
                {{-- Kies of het account actief of inactief is. --}}
                <label>Status *<select name="status"><option value="actief" @selected(old('status', $member->status ?? 'actief') === 'actief')>Actief</option><option value="inactief" @selected(old('status', $member->status) === 'inactief')>Inactief</option></select></label>
            </div>
            {{-- Toon extra uitleg als de beheerder het eigen account bewerkt. --}}
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
</div>
@endsection
