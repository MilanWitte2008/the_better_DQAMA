@extends('components.layout-dashboard')

@section('title', 'Docentdashboard · Ontwikkelplatform')
@section('welcome-title', 'Welkom terug, ' . ($user->voornaam ?? 'docent') . '!')
@section('welcome-subtitle', 'Jouw studenten, hun ontwikkeling en je volgende gesprek. Op één plek.')

@section('content')
@php
    $dashboard = app(\App\DocentDashboard::class)->data($user, request());
    extract($dashboard, EXTR_SKIP);
    $studentOptions = $students->mapWithKeys(fn ($item) => [$item->idstudent => $item->voornaam . ' ' . $item->achternaam . ' · ' . $item->klas])->all();
    $dateLabel = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d-m-Y H:i') : 'Nog geen update';
@endphp
<div class="docent-dashboard">

            @if (session('docent_success'))
                <div class="notice success save-feedback" role="status" tabindex="-1">{{ session('docent_success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="notice validation-feedback" role="alert" tabindex="-1"><strong>Controleer je
                        invoer.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @unless ($coachLinked)
                <div class="notice" role="status">Je account heeft nog geen coachprofiel. Vraag de beheerder om je account
                    aan een coachprofiel te koppelen.</div>
            @endunless
            <section class="hero" aria-label="Samenvatting">
                <div><span class="hero-tag">JOUW BEGELEIDING MAAKT HET VERSCHIL</span>
                    <h2>Ruimte om te groeien.<br>Aandacht voor iedereen.</h2>
                    <p>{{ $attentionCount }} {{ $attentionCount === 1 ? 'student heeft' : 'studenten hebben' }} nog
                        geen voortgangsupdate of geen update in de afgelopen 30 dagen.</p><a class="button"
                        href="{{ route('dashboard', ['filter' => 'aandacht']) }}#studenten">Bekijk aandachtspunten
                        <span>?</span></a>
                </div>
                <div class="hero-art" aria-hidden="true"><span class="orbit one"></span><span
                        class="orbit two"></span><span class="orbit three"></span><span class="art-center">?</span><span
                        class="art-label">Elke stap telt.</span></div>
            </section>
            <section class="metrics" aria-label="Kerncijfers">
                @foreach ([['Mijn studenten', $students->count(), 'Actuele begeleidingskoppelingen', '?'], ['Geplande gesprekken', $upcomingCount, 'Vanaf dit moment', '?'], ['Behaalde leeruitkomsten', $achieved . ' / ' . $total, 'Van jouw studenten', '?'], ['Mijn notities', $notesCount, 'Bij gekoppelde studenten', '?']] as [$label, $value, $hint, $icon])
                    <article class="metric">
                        <div><span>{{ $label }}</span><span class="metric-icon">{{ $icon }}</span>
                        </div><strong>{{ $value }}</strong><small>{{ $hint }}</small>
                    </article>
                @endforeach
            </section>
            <div class="columns">
                <section class="card students" id="studenten" data-dashboard-section="studenten">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">BEGELEIDING</p>
                            <h2>Mijn studenten <span class="count">{{ $students->count() }}</span></h2>
                        </div><span class="live">Uit de database</span>
                    </div>
                    {{-- GET-filters delen dezelfde toegangsselectie als de detailweergave. --}}
                    @if($coachLinked)
<details class="edit-panel" id="student-toevoegen" @if(old('_form') === 'student-new') open @endif>
    <summary class="create-student-button">+ Student toevoegen</summary>
    <p class="footnote">Maak een studentaccount aan en koppel het direct aan jouw begeleiding. De student kan inloggen met dit e-mailadres en wachtwoord. Een bestaand account koppelen verloopt via de beheerder.</p>
    @php
    $docentForm = ['formId' => 'student-new', 'action' => route('docent.students.store'), 'method' => 'POST', 'fields' => [
        'voornaam' => ['label' => 'Voornaam', 'max' => 50, 'required' => true],
        'achternaam' => ['label' => 'Achternaam', 'max' => 50, 'required' => true],
        'email' => ['label' => 'E-mailadres', 'type' => 'email', 'max' => 100, 'required' => true],
        'password' => ['label' => 'Wachtwoord (minimaal 8 tekens)', 'type' => 'password', 'max' => 72, 'required' => true],
        'password_confirmation' => ['label' => 'Herhaal wachtwoord', 'type' => 'password', 'max' => 72, 'required' => true],
        'studentnummer' => ['label' => 'Studentnummer (optioneel)', 'max' => 20],
        'klas' => ['label' => 'Klas (optioneel)', 'max' => 20],
        'opleiding' => ['label' => 'Opleiding (optioneel)', 'max' => 50],
    ]];
@endphp
@php
    $formId = $docentForm['formId'];
    $action = $docentForm['action'];
    $method = $docentForm['method'] ?? 'POST';
    $fields = $docentForm['fields'];
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
        $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
    @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
</details>
@endif
            <form class="filters" method="GET" action="{{ route('dashboard') }}#studenten">
                        <label><span>Zoeken</span><input name="q" type="search" maxlength="100"
                                value="{{ $search }}" placeholder="Naam, e-mail of studentnummer"></label>
                        <label><span>Klas</span><select name="klas">
                                <option value="">Alle klassen</option>
                                @foreach ($classes as $item)
                                    <option @selected($class === $item)>{{ $item }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label><span>Selectie</span><select name="filter">
                                <option value="">Alle studenten</option>
                                <option value="aandacht" @selected($attention)>Aandacht nodig</option>
                            </select></label>
                        <button class="button dark" type="submit">Zoeken</button>
                        @if ($search || $class || $attention)
                            <a href="{{ route('dashboard') }}#studenten">Wissen</a>
                        @endif
                    </form>
                    <div class="table-scroll">
                        <table>
                            <caption class="sr-only">Studenten en behaalde leeruitkomsten</caption>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Voortgang</th>
                                    <th>Laatste update</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($filtered as $student)
                                    <tr>
                                        <td>
                                            <div class="person"><span
                                                    class="avatar">{{ mb_strtoupper(mb_substr($student->voornaam ?? '', 0, 1) . mb_substr($student->achternaam ?? '', 0, 1)) }}</span>
                                                <div><strong>{{ $student->voornaam }}
                                                        {{ $student->achternaam }}</strong><small>{{ $student->klas ?: 'Geen klas' }}
                                                        · {{ $student->opleiding ?: 'Geen opleiding' }}</small></div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($student->progress !== null)
                                                <div class="progress-label">
                                                    <span>{{ $student->achieved }}/{{ $student->total }}
                                                        behaald</span><b>{{ $student->progress }}%</b></div><progress
                                                    value="{{ $student->progress }}" max="100"
                                                aria-label="Behaalde leeruitkomsten van {{ $student->voornaam }}">{{ $student->progress }}%</progress>@else<span
                                                    class="muted">Nog geen leeruitkomsten</span>
                                            @endif
                                        </td>
                                        <td><span
                                                class="pill {{ $student->attention ? 'amber' : '' }}">{{ $student->attention ? 'Aandacht nodig' : 'Recent bijgewerkt' }}</span><small>{{ $dateLabel($student->lastUpdate) }}</small>
                                        </td>
                                        <td>
                                            <div class="student-actions"><a class="button secondary"
                                                    href="{{ route('dashboard', array_merge(request()->only(['q', 'klas', 'filter']), ['student' => $student->idstudent])) }}#student-detail"
                                                    aria-label="Bewerk {{ $student->voornaam }} {{ $student->achternaam }}">Bewerken</a>
                                                <form method="POST"
                                                    action="{{ route('docent.students.destroy', $student->idstudent) }}"
                                                    data-confirm="Deze student uit jouw begeleiding verwijderen? Het account en dossier blijven bewaard.">
                                                    @csrf @method('DELETE')<button class="danger"
                                                        type="submit">Verwijderen</button></form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty<tr>
                                        <td colspan="4">
                                            <div class="empty">
                                                <strong>{{ $students->isEmpty() ? 'Nog geen studenten gekoppeld' : 'Geen studenten gevonden' }}</strong>
                                                <p>{{ $students->isEmpty() ? 'Zodra een actieve begeleiding is vastgelegd, verschijnen je studenten hier.' : 'Pas de zoekterm of filters aan om meer studenten te zien.' }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">{{ $filtered->count() }} van {{ $students->count() }} studenten ·
                        Alleen jouw actuele begeleiding</div>
                </section>
                <section class="card" id="rapportages" data-dashboard-section="rapportages">
                    <p class="eyebrow">ONTWIKKELING IN BEELD</p>
                    <h2>Elke stap vooruit</h2>@php
                        $percentage = $total ? (int) round(($achieved / $total) * 100) : 0;
                    @endphp<div class="ring"
                        style="--progress: {{ $percentage }}%">
                        <div><strong>{{ $total ? $percentage . '%' : '—' }}</strong><span>behaald</span></div>
                    </div>
                    <p class="center muted">{{ $achieved }} van {{ $total }} toegewezen leeruitkomsten
                        behaald.</p>
                    <div class="report-row"><span><i></i>Behaald</span><strong>{{ $achieved }}</strong></div>
                    <div class="report-row"><span><i class="light"></i>Nog niet
                            behaald</span><strong>{{ $total - $achieved }}</strong></div>
                    <p class="footnote">Gebaseerd op de vastgelegde status van leeruitkomsten, niet op een schatting.
                    </p>
                </section>
            </div>
            @if ($selected)
                <section class="card detail" id="student-detail">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">STUDENTDOSSIER</p>
                            <h2>{{ $selected->voornaam }} {{ $selected->achternaam }}</h2>
                            <p>{{ $selected->studentnummer }} · {{ $selected->opleiding }} · {{ $selected->email }}
                            </p>
                        </div><a
                            href="{{ route('dashboard', request()->only(['q', 'klas', 'filter'])) }}#studenten">Sluiten
                            ×</a>
                    </div>
                    <details class="edit-panel" open>
                        <summary>Studentgegevens bewerken</summary>
                        @php
    $docentForm = [
                            'formId' => 'student-' . $selected->idstudent,
                            'action' => route('docent.students.update', $selected->idstudent),
                            'method' => 'PUT',
                            'fields' => [
                                'klas' => ['label' => 'Klas', 'value' => $selected->klas, 'max' => 20],
                                'opleiding' => [
                                    'label' => 'Opleiding',
                                    'value' => $selected->opleiding,
                                    'max' => 50,
                                ],
                            ],
                        ];
@endphp
@php
    $formId = $docentForm['formId'];
    $action = $docentForm['action'];
    $method = $docentForm['method'] ?? 'POST';
    $fields = $docentForm['fields'];
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
        $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
    @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
                    </details>
                    @if ($availableOutcomes->isNotEmpty())
                        <details class="edit-panel" open>
                            <summary>Leeruitkomst toevoegen</summary>
                            @php
    $docentForm = [
                                'formId' => 'outcome-new',
                                'action' => route('docent.outcomes.store', $selected->idstudent),
                                'method' => 'POST',
                                'fields' => [
                                    'leeruitkomsten_leeruitkomsten_id' => [
                                        'label' => 'Leeruitkomst',
                                        'type' => 'select',
                                        'options' => $availableOutcomes->mapWithKeys(
                                                fn($item) => [
                                                    $item->leeruitkomsten_id => $item->code . ' · ' . $item->titel,
                                                ])->all(),
                                        'required' => true,
                                    ],
                                ],
                            ];
@endphp
@php
    $formId = $docentForm['formId'];
    $action = $docentForm['action'];
    $method = $docentForm['method'] ?? 'POST';
    $fields = $docentForm['fields'];
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
        $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
    @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
                        </details>
                    @endif
                    <div class="outcomes">
                        @forelse($outcomes->where('student_idstudent', $selected->idstudent) as $outcome)
                            <article><small>{{ $outcome->code }}</small>
                                <h3>{{ $outcome->titel }}</h3><span
                                    class="pill">{{ $outcome->status ?: 'Geen status' }}</span><small>Bijgewerkt:
                                    {{ $dateLabel($outcome->laatste_update) }}</small>
                                <details class="edit-panel" @if (old('_form') === 'outcome-' . $outcome->id_leeruitkomsten) open @endif>
                                    <summary>Voortgang bewerken</summary>
                                    @php
    $docentForm = [
                                        'formId' => 'outcome-' . $outcome->id_leeruitkomsten,
                                        'action' => route('docent.outcomes.update', $outcome->id_leeruitkomsten),
                                        'method' => 'PUT',
                                        'fields' => [
                                            'status' => [
                                                'label' => 'Status',
                                                'type' => 'select',
                                                'value' => ucfirst(strtolower($outcome->status ?? 'Te doen')),
                                                'options' => [
                                                    'Te doen' => 'Te doen',
                                                    'Bezig' => 'Bezig',
                                                    'Behaald' => 'Behaald',
                                                ],
                                                'required' => true,
                                            ],
                                            'opmerking' => [
                                                'label' => 'Toelichting',
                                                'type' => 'textarea',
                                                'value' => $outcome->opmerking,
                                            ],
                                        ],
                                    ];
@endphp
@php
    $formId = $docentForm['formId'];
    $action = $docentForm['action'];
    $method = $docentForm['method'] ?? 'POST';
    $fields = $docentForm['fields'];
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
        $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
    @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
                                </details>
                        </article>@empty<p class="empty">Voor deze student zijn nog geen leeruitkomsten
                                vastgelegd.</p>
                        @endforelse
                    </div>
                </section>
            @elseif(request()->has('student'))
                <div class="notice" role="status">Deze student is niet beschikbaar binnen jouw huidige begeleiding.
                </div>
            @endif
            <div class="bottom-columns">
                <section class="card" id="gesprekken" data-dashboard-section="gesprekken">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">TIJD VOOR EEN GESPREK</p>
                            <h2>Coachgesprekken</h2>
                        </div><span class="count">{{ $upcomingCount }} gepland</span>
                    </div>
                    @if ($students->isNotEmpty())
                        <details class="edit-panel" @if (old('_form') === 'meeting-new') open @endif>
                            <summary>+ Gesprek plannen</summary>
                            @php
    $docentForm = [
                                'formId' => 'meeting-new',
                                'action' => route('docent.meetings.store'),
                                'method' => 'POST',
                                'fields' => [
                                    'student_idstudent' => [
                                        'label' => 'Student',
                                        'type' => 'select',
                                        'options' => $studentOptions,
                                        'required' => true,
                                    ],
                                    'onderwerp' => ['label' => 'Onderwerp', 'required' => true],
                                    'datum' => [
                                        'label' => 'Datum en tijd',
                                        'type' => 'datetime-local',
                                        'required' => true,
                                    ],
                                    'inhoud' => ['label' => 'Gespreksverslag', 'type' => 'textarea'],
                                    'afspraken' => ['label' => 'Afspraken', 'type' => 'textarea'],
                                ],
                            ];
@endphp
@php
    $formId = $docentForm['formId'];
    $action = $docentForm['action'];
    $method = $docentForm['method'] ?? 'POST';
    $fields = $docentForm['fields'];
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
        $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
    @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
                        </details>
                    @endif
                    @forelse($upcoming as $meeting)
                        <article class="meeting">
                            <div class="calendar">
                                <strong>{{ \Illuminate\Support\Carbon::parse($meeting->datum)->format('d') }}</strong><small>{{ \Illuminate\Support\Carbon::parse($meeting->datum)->format('m.Y') }}</small>
                            </div>
                            <div>
                                <h3>{{ $meeting->onderwerp ?: 'Coachgesprek' }}</h3>
                                <p>{{ $meeting->voornaam }} {{ $meeting->achternaam }}</p>
                                <small>{{ $dateLabel($meeting->datum) }}</small>
                                @if ($meeting->inhoud || $meeting->afspraken)
                                    <details>
                                        <summary>Gespreksdetails</summary>
                                        <p class="preserve">{{ $meeting->inhoud }}</p>
                                        <p class="preserve">{{ $meeting->afspraken }}</p>
                                    </details>
                                @endif<details class="edit-panel" @if(old('_form') === 'meeting-'.$meeting->idgesprek) open @endif>
    <summary>Gesprek bewerken</summary>
    @php
    $docentForm = ['formId' => 'meeting-'.$meeting->idgesprek, 'action' => route('docent.meetings.update', $meeting->idgesprek), 'method' => 'PUT', 'fields' => [
        'onderwerp' => ['label' => 'Onderwerp', 'value' => $meeting->onderwerp, 'required' => true],
        'datum' => ['label' => 'Datum en tijd', 'type' => 'datetime-local', 'value' => $meeting->datum ? \Illuminate\Support\Carbon::parse($meeting->datum)->format('Y-m-d\TH:i') : '', 'required' => true],
        'inhoud' => ['label' => 'Gespreksverslag', 'type' => 'textarea', 'value' => $meeting->inhoud],
        'afspraken' => ['label' => 'Afspraken', 'type' => 'textarea', 'value' => $meeting->afspraken],
    ]];
@endphp
@php
    $formId = $docentForm['formId'];
    $action = $docentForm['action'];
    $method = $docentForm['method'] ?? 'POST';
    $fields = $docentForm['fields'];
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
        $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
    @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
    <form method="POST" action="{{ route('docent.meetings.destroy', $meeting->idgesprek) }}" data-confirm="Dit gesprek verwijderen?">@csrf @method('DELETE')<button class="danger" type="submit">Gesprek verwijderen</button></form>
</details>
                            </div>
                    </article>@empty<div class="empty"><span class="empty-symbol">?</span><strong>Even ruimte in
                                de agenda</strong>
                            <p>Er zijn geen komende gesprekken vastgelegd voor jouw studenten.</p>
                        </div>
                    @endforelse
                    <details class="history">
                        <summary>Eerdere gesprekken (laatste {{ $recent->count() }})</summary>
                        @forelse($recent as $meeting)
                            <article class="history-item">
                                <strong>{{ $meeting->onderwerp ?: 'Coachgesprek' }}</strong><small>{{ $meeting->voornaam }}
                                    {{ $meeting->achternaam }} · {{ $dateLabel($meeting->datum) }}</small>
                                <p class="preserve">{{ $meeting->inhoud }}</p>
                                <p class="preserve">{{ $meeting->afspraken }}</p><details class="edit-panel" @if(old('_form') === 'meeting-'.$meeting->idgesprek) open @endif>
    <summary>Gesprek bewerken</summary>
    @php
    $docentForm = ['formId' => 'meeting-'.$meeting->idgesprek, 'action' => route('docent.meetings.update', $meeting->idgesprek), 'method' => 'PUT', 'fields' => [
        'onderwerp' => ['label' => 'Onderwerp', 'value' => $meeting->onderwerp, 'required' => true],
        'datum' => ['label' => 'Datum en tijd', 'type' => 'datetime-local', 'value' => $meeting->datum ? \Illuminate\Support\Carbon::parse($meeting->datum)->format('Y-m-d\TH:i') : '', 'required' => true],
        'inhoud' => ['label' => 'Gespreksverslag', 'type' => 'textarea', 'value' => $meeting->inhoud],
        'afspraken' => ['label' => 'Afspraken', 'type' => 'textarea', 'value' => $meeting->afspraken],
    ]];
@endphp
@php
    $formId = $docentForm['formId'];
    $action = $docentForm['action'];
    $method = $docentForm['method'] ?? 'POST';
    $fields = $docentForm['fields'];
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
        $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
    @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
    <form method="POST" action="{{ route('docent.meetings.destroy', $meeting->idgesprek) }}" data-confirm="Dit gesprek verwijderen?">@csrf @method('DELETE')<button class="danger" type="submit">Gesprek verwijderen</button></form>
</details>
                        </article>@empty<p class="muted">Nog geen eerdere gesprekken.</p>
                        @endforelse
                    </details>
                </section>
                <section class="card" id="notities" data-dashboard-section="notities">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">VASTGELEGD OM TE ONTHOUDEN</p>
                            <h2>Mijn recente notities</h2>
                        </div><span class="count">{{ $notesCount }}</span>
                    </div>
                    @if ($students->isNotEmpty())
                        <details class="edit-panel" @if (old('_form') === 'note-new') open @endif>
                            <summary>+ Notitie toevoegen</summary>
                            @php
    $docentForm = [
                                'formId' => 'note-new',
                                'action' => route('docent.notes.store'),
                                'method' => 'POST',
                                'fields' => [
                                    'idstudent' => [
                                        'label' => 'Student',
                                        'type' => 'select',
                                        'options' => $studentOptions,
                                        'required' => true,
                                    ],
                                    'type' => [
                                        'label' => 'Type',
                                        'type' => 'select',
                                        'options' => [
                                            'Notitie' => 'Notitie',
                                            'Voortgang' => 'Voortgang',
                                            'Afspraak' => 'Afspraak',
                                        ],
                                        'required' => true,
                                    ],
                                    'inhoud' => ['label' => 'Notitie', 'type' => 'textarea', 'required' => true],
                                ],
                            ];
@endphp
@php
    $formId = $docentForm['formId'];
    $action = $docentForm['action'];
    $method = $docentForm['method'] ?? 'POST';
    $fields = $docentForm['fields'];
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
        $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
    @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
                        </details>
                    @endif
                    @forelse($notes as $note)
                        <article class="note">
                            <div><strong>{{ $note->voornaam }}
                                    {{ $note->achternaam }}</strong><small>{{ $dateLabel($note->datum) }}</small>
                            </div>
                            <p class="preserve">{{ $note->inhoud ?: 'Geen inhoud vastgelegd.' }}</p><span
                                class="note-type">{{ $note->type ?: 'Notitie' }}</span>
                            <details class="edit-panel" @if (old('_form') === 'note-' . $note->id_notitie) open @endif>
                                <summary>Notitie bewerken</summary>
                                @php
    $docentForm = [
                                    'formId' => 'note-' . $note->id_notitie,
                                    'action' => route('docent.notes.update', $note->id_notitie),
                                    'method' => 'PUT',
                                    'fields' => [
                                        'type' => [
                                            'label' => 'Type',
                                            'value' => $note->type ?: 'Notitie',
                                            'max' => 50,
                                            'required' => true,
                                        ],
                                        'inhoud' => [
                                            'label' => 'Notitie',
                                            'type' => 'textarea',
                                            'value' => $note->inhoud,
                                            'required' => true,
                                        ],
                                    ],
                                ];
@endphp
@php
    $formId = $docentForm['formId'];
    $action = $docentForm['action'];
    $method = $docentForm['method'] ?? 'POST';
    $fields = $docentForm['fields'];
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
        $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
    @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
                                <form method="POST" action="{{ route('docent.notes.destroy', $note->id_notitie) }}"
                                    data-confirm="Deze notitie verwijderen?">@csrf @method('DELETE')<button
                                        class="danger" type="submit">Notitie verwijderen</button></form>
                            </details>
                    </article>@empty<div class="empty"><span class="empty-symbol">?</span><strong>Een frisse
                                start</strong>
                            <p>Je hebt nog geen notities bij je huidige studenten vastgelegd.</p>
                        </div>
                    @endforelse
                    <p class="footnote">De laatste acht notities die aan jouw coachprofiel zijn gekoppeld.</p>
                </section>
            </div>
            <footer>Ontwikkelplatform <span>Gegevens opgehaald op {{ now()->format('d-m-Y H:i') }} · <a
                        href="#inhoud">Terug naar boven ?</a></span></footer>
<script>
/* De actieve navigatie volgt de URL, ook bij terug/vooruit en toetsenbordbediening. */
(() => {
const links = [...document.querySelectorAll('.sidebar nav a')];
const title = document.getElementById('section-title');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const activate = (id, animate = false) => {
const target = id === 'student-detail' ? 'studenten' : id;
links.forEach(link => {
const active = link.hash === '#' + target;
link.classList.toggle('active', active);
if (active) {
link.setAttribute('aria-current', 'location');
title.textContent = link.querySelector('span').textContent;
} else link.removeAttribute('aria-current');
});
const section = document.getElementById(id);
if (animate && section && !reducedMotion.matches) {
section.animate([{opacity: .5, transform: 'translateY(8px)'}, {opacity: 1, transform: 'translateY(0)'}], {duration: 300,
easing: 'ease-out'});
}
};
const known = id => links.some(link => link.hash === '#' + id) || id === 'student-detail';
const fromHash = () => {
const id = location.hash.slice(1);
activate(known(id) ? id : 'inhoud', true);
};
links.forEach(link => link.addEventListener('click', () => {
if (location.hash === link.hash) activate(link.hash.slice(1), true);
}));
window.addEventListener('hashchange', fromHash);
fromHash();
const error = document.querySelector('.validation-feedback');
if (error) { error.focus(); error.scrollIntoView({block: 'center'}); }
const feedback = document.querySelector('.save-feedback');
if (feedback) {
const close = document.createElement('button');
close.type = 'button'; close.textContent = 'Ã—'; close.setAttribute('aria-label', 'Melding sluiten');
close.addEventListener('click', () => feedback.remove()); feedback.append(close);
}
document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
if (!confirm(form.dataset.confirm)) event.preventDefault();
}));
})();

</script>

</div>
@endsection
