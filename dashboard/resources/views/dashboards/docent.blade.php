{{-- De gegevensservice is uitsluitend gekoppeld aan deze view; andere dashboards blijven ongewijzigd. --}}
@php
    $dashboard = app(\App\DocentDashboard::class)->data($user, request());
    extract($dashboard, EXTR_SKIP);
    $studentOptions = $students->isNotEmpty()
        ? $students->mapWithKeys(fn($item) => [$item->idstudent => $item->voornaam . ' ' . $item->achternaam . ' · ' . $item->klas])->all()
        : $teacherGroupStudents->mapWithKeys(fn($item) => [$item->idlid => $item->voornaam . ' ' . $item->achternaam . ' · ' . $item->groepnaam])->all();
    $dateLabel = fn($value) => $value
        ? \Illuminate\Support\Carbon::parse($value)->format('d-m-Y H:i')
        : 'Nog geen update';
@endphp
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Docentdashboard · Ontwikkelplatform</title>
    <script src="{{ asset('js/dashboard-notifications.js') }}?v={{ filemtime(public_path('js/dashboard-notifications.js')) }}" defer></script>
    <style>
        @include('dashboards.docent-style') @include('components.dashboard-navigation-style')
    </style>
</head>

<body class="docent-page dashboard-navigation">
    <a class="skip" href="#inhoud">Naar inhoud</a>
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <x-firda-wordmark class="firda-sidebar" />
            <span><strong>Ontwikkelplatform</strong><small>Persoonlijke groei</small></span>
        </a>
        <div class="profile-chip">
            <span
                class="avatar">{{ mb_strtoupper(mb_substr($user->voornaam ?? '', 0, 1) . mb_substr($user->achternaam ?? '', 0, 1)) }}</span>
            <span><strong>{{ $user->voornaam }} {{ $user->achternaam }}</strong><small>Docent</small></span>
        </div>
        <nav aria-label="Hoofdnavigatie">
            <a href="#inhoud" aria-label="Overzicht">◈ <span>Overzicht</span></a>
            <a href="#studenten" aria-label="Mijn studenten">♙ <span>Mijn
                    studenten</span><b>{{ $studentCount }}</b></a>
            <a href="#gesprekken" aria-label="Coachgesprekken">◷ <span>Coachgesprekken</span></a>
            <a href="#notities" aria-label="Notities">▤ <span>Notities</span></a>
            <a href="#rapportages" aria-label="Voortgang">▥ <span>Voortgang</span></a>
        </nav>
        <div class="sidebar-footer"><strong>Docentomgeving</strong><small>Persoonlijk dashboard</small></div>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <div>
                <h1>Goedemiddag, {{ $user->voornaam }}! <span aria-hidden="true">👋</span></h1>
                <p>Bekijk hier jouw persoonlijke ontwikkeling en voortgang.</p>
                <span id="section-title" class="sr-only">Overzicht</span>
            </div>
            <div class="topbar-actions">
                @include('components.notification-button')
                <span
                    class="avatar avatar-light">{{ mb_strtoupper(mb_substr($user->voornaam ?? '', 0, 1) . mb_substr($user->achternaam ?? '', 0, 1)) }}</span>
                <form method="POST" action="{{ url('/logout') }}">@csrf<button class="logout-button"
                        type="submit">Uitloggen</button></form>
            </div>
        </header>
        <main id="inhoud">
            <div class="page-heading">
                <div>
                    <p class="eyebrow">SAMEN WERKEN AAN GROEI</p>
                    <p>Jouw studenten, hun ontwikkeling en je volgende gesprek. Op één plek.</p>
                </div>
            </div>
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
                        <span>↗</span></a>
                </div>
                <div class="hero-art" aria-hidden="true"><span class="orbit one"></span><span
                        class="orbit two"></span><span class="orbit three"></span><span class="art-center">↗</span><span
                        class="art-label">Elke stap telt.</span></div>
            </section>
            <section class="metrics" aria-label="Kerncijfers">
                @foreach ([['Mijn studenten', $studentCount, 'Studenten in jouw klassen', '♙'], ['Geplande gesprekken', $upcomingCount, 'Vanaf dit moment', '◷'], ['Behaalde leeruitkomsten', $achieved . ' / ' . $total, 'Van jouw studenten', '✓'], ['Mijn notities', $notesCount, 'Bij gekoppelde studenten', '▤']] as [$label, $value, $hint, $icon])
                    <article class="metric">
                        <div><span>{{ $label }}</span><span class="metric-icon">{{ $icon }}</span>
                        </div><strong>{{ $value }}</strong><small>{{ $hint }}</small>
                    </article>
                @endforeach
            </section>
            <div class="columns">
                <section class="card students" id="studenten">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">BEGELEIDING</p>
                            <h2>Mijn studenten <span class="count">{{ $studentCount }}</span></h2>
                        </div>
                        <div class="student-group-summary">
                            <span class="live">Uit de database</span>
                            @if ($teacherGroups->isNotEmpty())
                                <div class="teacher-class-list" aria-label="Jouw klas">
                                    <span>Jouw klas{{ $teacherGroups->count() > 1 ? 'sen' : '' }}:</span>
                                    @foreach ($teacherGroups as $group)
                                        <span class="count">{{ $group->groepnaam ?: 'Groep ' . $group->idgroep }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="muted teacher-class-empty">Geen klas gekoppeld</span>
                            @endif
                        </div>
                    </div>
                    {{-- GET-filters delen dezelfde toegangsselectie als de detailweergave. --}}
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
                                    <th>Klas</th>
                                    <th>Voortgang</th>
                                    <th>Laatste update</th>
                                    <th><span class="sr-only">Details</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filtered as $student)
                                    <tr>
                                        <td>
                                            <div class="person"><span
                                                    class="avatar">{{ mb_strtoupper(mb_substr($student->voornaam ?? '', 0, 1) . mb_substr($student->achternaam ?? '', 0, 1)) }}</span>
                                                <div><strong>{{ $student->voornaam }}
                                                        {{ $student->achternaam }}</strong><small>{{ $student->klas ?: 'Geen klas' }}
                                                        · {{ $student->opleiding ?: 'Geen opleiding' }}</small></div>
                                            </div>
                                        </td>
                                        <td>{{ $student->klas ?: 'Niet ingesteld' }}</td>
                                        <td>
                                            @if ($student->progress !== null)
                                                <div class="progress-label">
                                                    <span>{{ $student->achieved }}/{{ $student->total }}
                                                        behaald</span><b>{{ $student->progress }}%</b>
                                                </div><progress value="{{ $student->progress }}" max="100"
                                                aria-label="Behaalde leeruitkomsten van {{ $student->voornaam }}">{{ $student->progress }}%</progress>@else<span
                                                    class="muted">Nog geen leeruitkomsten</span>
                                            @endif
                                        </td>
                                        <td><span
                                                class="pill {{ $student->attention ? 'amber' : '' }}">{{ $student->attention ? 'Aandacht nodig' : 'Recent bijgewerkt' }}</span><small>{{ $dateLabel($student->lastUpdate) }}</small>
                                        </td>
                                        <td>
                                            <a class="detail-link"
                                                href="{{ route('dashboard', array_merge(request()->only(['q', 'klas', 'filter']), ['student' => $student->idstudent])) }}#student-detail"
                                                aria-label="Bekijk {{ $student->voornaam }} {{ $student->achternaam }}">Bekijk ↗</a>
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach ($visibleGroupStudents as $groupStudent)
                                    <tr>
                                        <td>
                                            <div class="person"><span
                                                    class="avatar">{{ mb_strtoupper(mb_substr($groupStudent->voornaam ?? '', 0, 1) . mb_substr($groupStudent->achternaam ?? '', 0, 1)) }}</span>
                                                <div><strong>{{ $groupStudent->voornaam }}
                                                        {{ $groupStudent->achternaam }}</strong><small>Leerling in {{ $groupStudent->groepnaam }}</small></div>
                                            </div>
                                        </td>
                                        <td>{{ $groupStudent->groepnaam }}</td>
                                        <td><span class="muted">Nog geen voortgang beschikbaar</span></td>
                                        <td><span class="muted">Nog geen updates</span></td>
                                        <td></td>
                                    </tr>
                                @endforeach
                                @if ($filtered->isEmpty() && $visibleGroupStudents->isEmpty())
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty">
                                                <strong>{{ $students->isEmpty() && $teacherGroupStudents->isEmpty() ? 'Nog geen leerlingen gekoppeld' : 'Geen leerlingen gevonden' }}</strong>
                                                <p>{{ $students->isEmpty() && $teacherGroupStudents->isEmpty() ? 'Zodra leerlingen aan jouw klas zijn gekoppeld, verschijnen ze hier.' : 'Pas de zoekterm of filters aan om meer leerlingen te zien.' }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">{{ $filtered->count() + $visibleGroupStudents->count() }} leerlingen in de lijst</div>
                </section>
                <section class="card" id="rapportages">
                    <p class="eyebrow">ONTWIKKELING IN BEELD</p>
                    <h2>Elke stap vooruit</h2>
                    @php
                        $percentage = $total ? (int) round(($achieved / $total) * 100) : 0;
                    @endphp
                    <div class="ring"
                        style="--progress: {{ $percentage }}%">
                        <div><strong
                                @if (!$total) style="font-size:16px;letter-spacing:0" @endif>{{ $total ? $percentage . '%' : 'Nog geen data' }}</strong><span>behaald</span>
                        </div>
                    </div>
                    @if ($total === 0)
                        <p class="center muted">Er zijn nog geen leeruitkomsten aan jouw studenten toegewezen. Voeg
                            eerst een leeruitkomst toe om voortgang te meten.</p>
                        @if ($studentOptions !== [])
                            <form method="GET" action="{{ route('dashboard') }}#student-detail"
                                class="docent-form">
                                <label for="progress-student">Kies een student<select id="progress-student"
                                        name="student">
                                        @foreach ($studentOptions as $id => $label)
                                            <option value="{{ $id }}">{{ $label }}</option>
                                        @endforeach
                                    </select></label>
                                <button type="submit" class="button dark">Dossier bekijken</button>
                            </form>
                        @else<p class="center muted">Voeg eerst een student toe via Mijn studenten.</p>
                        @endif
                    @else
                        <p class="center muted">{{ $achieved }} van {{ $total }} toegewezen leeruitkomsten
                            behaald.</p>
                    @endif
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
                        </div><a
                            href="{{ route('dashboard', request()->only(['q', 'klas', 'filter'])) }}#studenten">Sluiten
                            ×</a>
                    </div>
                    <dl class="student-facts">
                            @if ($selected->studentnummer)
                                <div><dt>Studentnummer</dt><dd>{{ $selected->studentnummer }}</dd></div>
                            @endif
                            @if ($selected->klas)
                                <div><dt>Klas</dt><dd>{{ $selected->klas }}</dd></div>
                            @endif
                            @if ($selected->opleiding)
                                <div><dt>Opleiding</dt><dd>{{ $selected->opleiding }}</dd></div>
                            @endif
                            @if ($selected->email)
                                <div><dt>E-mailadres</dt><dd>{{ $selected->email }}</dd></div>
                            @endif
                    </dl>
                    <h3 class="dossier-section-title">Leeruitkomsten</h3>
                    <div class="dossier-outcomes">
                        @forelse($outcomes->where('student_idstudent', $selected->idstudent) as $outcome)
                            <article class="dossier-outcome">
                                <div class="dossier-outcome-main">
                                        @if ($outcome->fase_naam)<small>{{ $outcome->fase_naam }}</small>@endif
                                        @if ($outcome->code)<small>{{ $outcome->code }}</small>@endif
                                    <h4>{{ $outcome->titel }}</h4>
                                        @if ($outcome->omschrijving)
                                            <p class="preserve">{{ $outcome->omschrijving }}</p>
                                        @endif
                                    @if($outcome->opmerking)
                                        <p class="preserve">{{ $outcome->opmerking }}</p>
                                    @endif
                                </div>
                                    @if ($outcome->status)
                                        <span @class(['pill', 'dossier-status', 'dossier-status-done' => strtolower($outcome->status) === 'behaald', 'dossier-status-active' => strtolower($outcome->status) === 'bezig'])>{{ $outcome->status }}</span>
                                    @endif
                                    @if ($outcome->laatste_update)
                                        <div class="dossier-updated"><span>Bijgewerkt</span><time>{{ $dateLabel($outcome->laatste_update) }}</time></div>
                                    @endif
                            </article>
                        @empty
                            <p class="empty">Voor deze student zijn nog geen leeruitkomsten vastgelegd.</p>
                        @endforelse
                    </div>
                </section>
            @elseif(request()->has('student'))
                <div class="notice" role="status">Deze student is niet beschikbaar binnen jouw huidige begeleiding.
                </div>
            @endif
            <div class="bottom-columns">
                <section class="card" id="gesprekken">
                    @php
                        $agendaMeetings = $upcoming->concat($meetingRequests)
                            ->sortBy(fn ($meeting) => $meeting->datum ?? '9999-12-31 23:59:59')
                            ->values();
                    @endphp
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">TIJD VOOR EEN GESPREK</p>
                            <h2>Coachgesprekken</h2>
                        </div><span class="count">{{ $upcomingCount }} gepland</span>
                    </div>
                    @if ($studentOptions !== [])
                        <details class="edit-panel" @if (old('_form') === 'meeting-new') open @endif>
                            <summary>+ Gesprek plannen</summary>
                            @include('dashboards.docent-form', [
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
                            ])
                        </details>
                    @endif
                    @forelse($agendaMeetings as $meeting)
                        <article class="meeting">
                            <div class="calendar">
                                <strong>{{ \Illuminate\Support\Carbon::parse($meeting->datum)->format('d') }}</strong><small>{{ \Illuminate\Support\Carbon::parse($meeting->datum)->format('m.Y') }}</small>
                            </div>
                            <div>
                                <h3>{{ $meeting->onderwerp ?: 'Coachgesprek' }}</h3>
                                <p>{{ $meeting->voornaam }} {{ $meeting->achternaam }}</p>
                                @if ($meeting->onderwerp === 'Gespreksaanvraag')
                                    <small>{{ $meeting->datum ? $dateLabel($meeting->datum) : 'Geen datum opgegeven' }}</small>
                                    <p><strong>
                                        @if ($meeting->afspraken === 'approved')
                                            Goedgekeurd
                                        @elseif ($meeting->afspraken === 'rejected')
                                            Afgekeurd
                                        @else
                                            Aangevraagd
                                        @endif
                                    </strong></p>
                                    @if ($meeting->afspraken === 'pending')
                                        <div class="meeting-request-actions">
                                            <form method="POST" action="{{ route('docent.meeting-requests.approve', $meeting->idgesprek) }}">
                                                @csrf @method('PUT')
                                                <button class="button" type="submit">Goedkeuren</button>
                                            </form>
                                            <form method="POST" action="{{ route('docent.meeting-requests.reject', $meeting->idgesprek) }}">
                                                @csrf @method('PUT')
                                                <button class="danger" type="submit">Afkeuren</button>
                                            </form>
                                        </div>
                                    @endif
                                @else
                                    <small>{{ $dateLabel($meeting->datum) }}</small>
                                @endif
                                @if ($meeting->inhoud || $meeting->afspraken)
                                    <details>
                                        <summary>Gespreksdetails</summary>
                                        <p class="preserve">{{ $meeting->inhoud }}</p>
                                        @if ($meeting->onderwerp !== 'Gespreksaanvraag')
                                            <p class="preserve">{{ $meeting->afspraken }}</p>
                                        @endif
                                    </details>
                                @endif
                                @if ($meeting->onderwerp !== 'Gespreksaanvraag')
                                    @include('dashboards.docent-meeting-edit')
                                @endif
                            </div>
                    </article>@empty<div class="empty"><span class="empty-symbol">◷</span><strong>Even ruimte in
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
                                <p class="preserve">{{ $meeting->afspraken }}</p>
                                @include('dashboards.docent-meeting-edit')
                        </article>@empty<p class="muted">Nog geen eerdere gesprekken.</p>
                        @endforelse
                    </details>
                </section>
                <section class="card" id="notities">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">VASTGELEGD OM TE ONTHOUDEN</p>
                            <h2>Mijn recente notities</h2>
                        </div><span class="count">{{ $notesCount }}</span>
                    </div>
                    @if ($studentOptions !== [])
                        <details class="edit-panel" @if (old('_form') === 'note-new') open @endif>
                            <summary>+ Notitie toevoegen</summary>
                            @include('dashboards.docent-form', [
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
                            ])
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
                                @include('dashboards.docent-form', [
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
                                ])
                                <form method="POST" action="{{ route('docent.notes.destroy', $note->id_notitie) }}"
                                    data-confirm="Deze notitie verwijderen?">@csrf @method('DELETE')<button
                                        class="danger" type="submit">Notitie verwijderen</button></form>
                            </details>
                    </article>@empty<div class="empty"><span class="empty-symbol">▤</span><strong>Een frisse
                                start</strong>
                            <p>Je hebt nog geen notities bij je huidige studenten vastgelegd.</p>
                        </div>
                    @endforelse
                    <p class="footnote">De laatste acht notities die aan jouw coachprofiel zijn gekoppeld.</p>
                </section>
            </div>
            <footer>Ontwikkelplatform <span>Gegevens opgehaald op {{ now()->format('d-m-Y H:i') }} · <a
                        href="#inhoud">Terug naar boven ↑</a></span></footer>
        </main>
    </div>
    <script>
        @include('dashboards.docent-script')
    </script>
</body>

</html>
