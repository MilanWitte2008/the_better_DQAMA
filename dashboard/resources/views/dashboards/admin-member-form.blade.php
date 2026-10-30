@extends('components.layout-dashboard')
@section('title', $member->exists ? 'Gebruiker bewerken' : 'Gebruiker toevoegen')
@section('welcome-title', 'Gebruikersbeheer')
@section('welcome-subtitle', 'Geef ieder account de juiste toegang tot het platform.')
@section('content')
<div class="admin-workspace admin-form-page">
<a class="admin-link" href="{{ route('dashboard') }}">← Terug naar het overzicht</a>
<section class="panel" style="margin-top:20px">
<div class="admin-panel-heading"><div><span class="admin-eyebrow">ACCOUNTBEHEER</span><h2>{{ $member->exists ? 'Gebruiker bewerken' : 'Nieuwe gebruiker' }}</h2><p>Velden met een * zijn verplicht.</p></div></div>
@if($errors->any())<div class="admin-alert admin-error" role="alert"><strong>Opslaan is niet gelukt.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="simple-form" method="POST" action="{{ $member->exists ? route('beheer.members.update', $member) : route('beheer.members.store') }}">
@csrf
@if($member->exists) @method('PUT') @endif
<div class="admin-form-grid">
@foreach(['voornaam' => 'Voornaam', 'achternaam' => 'Achternaam', 'email' => 'E-mailadres'] as $field => $label)
<label>{{ $label }} *<input name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, $member->$field) }}" maxlength="{{ $field === 'email' ? 100 : 50 }}" required @if($errors->has($field)) aria-invalid="true" @endif></label>
@endforeach
<label>Rol *<select name="rol_idrol" required><option value="">Selecteer een rol</option>@foreach($roles as $role)<option value="{{ $role->idrol }}" @selected(old('rol_idrol', $member->rol_idrol) == $role->idrol)>{{ $role->naam }}</option>@endforeach</select></label>
<label>Status *<select name="status"><option value="actief" @selected(old('status', $member->status ?? 'actief') === 'actief')>Actief</option><option value="inactief" @selected(old('status', $member->status) === 'inactief')>Inactief</option></select></label>
</div>
@if($member->is(auth()->user()))<p class="info-strip">Dit is je eigen account. Je rol en actieve status moeten behouden blijven.</p>@endif
<fieldset class="admin-password"><legend>{{ $member->exists ? 'Wachtwoord wijzigen (optioneel)' : 'Wachtwoord instellen' }}</legend><p>Minimaal 12 tekens. {{ $member->exists ? 'Laat beide velden leeg om het huidige wachtwoord te behouden.' : '' }}</p><div class="admin-form-grid"><label>Wachtwoord {{ $member->exists ? '' : '*' }}<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="128" @required(!$member->exists)></label><label>Wachtwoord herhalen {{ $member->exists ? '' : '*' }}<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="128" @required(!$member->exists)></label></div></fieldset>
<div class="admin-form-actions"><button class="admin-button" type="submit">{{ $member->exists ? 'Wijzigingen opslaan' : 'Gebruiker aanmaken' }}</button><a class="admin-link" href="{{ route('dashboard') }}">Annuleren</a></div>
</form>
</section></div>
@endsection
