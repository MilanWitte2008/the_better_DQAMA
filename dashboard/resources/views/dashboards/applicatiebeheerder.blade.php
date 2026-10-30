@extends('components.layout-dashboard')
@section('title', 'Dashboard · ITIG Applicatiebeheer')
@section('content')
    @php
        $monthNames = ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'];
        $chartColors = ['#155c9e', '#58afe0', '#f0ae72', '#858ad3', '#64bec5', '#9cb2cd'];
    @endphp
    <div class="admin-workspace" data-admin-dashboard>
        <div class="admin-intro">
            <div><span class="admin-eyebrow">JOUW PLATFORM, IN BEELD</span>
                <h1>Alles onder controle<span>.</span></h1>
                <p>Welkom terug, {{ auth()->user()->voornaam }}. Dit is jouw overzicht voor vandaag.</p>
            </div>
        </div>
        @if (session('success'))
            <div class="admin-alert" role="status"><img src="{{ asset('icons/tabler/check.svg') }}" alt=""
                    width="18" height="18">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="admin-alert admin-error" role="alert"><strong>Controleer je invoer.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="metric-grid" data-dashboard-section="overzicht" aria-label="Platformoverzicht">
            <article class="metric-card metric-mint">
                <div class="metric-label"><span>Gebruikers</span><img src="{{ asset('icons/tabler/users.svg') }}"
                        alt="" width="22" height="22"></div>
                <strong class="metric-value">{{ $stats['members'] }}</strong>
                <div class="metric-bottom"><span>Accounts in je platform</span><a href="#gebruikers"
                        aria-label="Gebruikers bekijken"><img src="{{ asset('icons/tabler/arrow-up-right.svg') }}"
                            alt="" width="19" height="19"></a></div>
            </article>
            <article class="metric-card metric-teal">
                <div class="metric-label"><span>Actieve accounts</span><img src="{{ asset('icons/tabler/activity.svg') }}"
                        alt="" width="22" height="22"></div>
                <strong class="metric-value">{{ $stats['active'] }} <small>/ {{ $stats['members'] }}</small></strong>
                <div class="metric-bottom"><span>{{ $analytics['activePercent'] }}% heeft toegang</span><progress
                        value="{{ $analytics['activePercent'] }}" max="100"
                        aria-label="Percentage actieve accounts">{{ $analytics['activePercent'] }}%</progress></div>
            </article>
            <article class="metric-card metric-blue">
                <div class="metric-label"><span>Rollen & toegang</span><img
                        src="{{ asset('icons/tabler/shield-check.svg') }}" alt="" width="22" height="22">
                </div>
                <strong class="metric-value">{{ $stats['roles'] }}</strong>
                <div class="metric-bottom"><span>Een rol voor iedereen</span><a href="#rollen"
                        aria-label="Rollen bekijken"><img src="{{ asset('icons/tabler/arrow-up-right.svg') }}"
                            alt="" width="19" height="19"></a></div>
            </article>
        </section>

        <div class="overview-charts" data-dashboard-section="overzicht">
            <section class="panel growth-panel" aria-labelledby="growth-title">
                <div class="admin-panel-heading">
                    <div><span class="panel-kicker">PLATFORMGROEI</span>
                        <h2 id="growth-title">Nieuwe gebruikers</h2>
                    </div>
                    <form method="GET" action="{{ route('dashboard') }}" class="year-picker">
                        @foreach (collect($filters)->except('year') as $key => $value)
                            @if ($value !== null)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label class="sr-only" for="chart-year">Jaar van de grafiek</label>
                        <select id="chart-year" name="year">
                            @foreach (collect(range(now()->year, max(2000, now()->year - 5)))->push($analytics['year'])->unique()->sortDesc() as $year)
                                <option value="{{ $year }}" @selected($analytics['year'] === $year)>{{ $year }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="year-submit" aria-label="Geselecteerd jaar tonen"><img
                                src="{{ asset('icons/tabler/arrow-right.svg') }}" alt="" width="16"
                                height="16"></button>
                    </form>
                </div>
                <div class="growth-summary"><strong>{{ $analytics['yearTotal'] }}</strong><span>nieuwe accounts in
                        {{ $analytics['year'] }}</span><span class="chart-legend"><i></i> Registraties per maand</span>
                </div>
                <div class="growth-canvas-wrap"><canvas id="registrations-chart" role="img"
                        aria-label="Registraties per maand in {{ $analytics['year'] }}. De volledige cijfers staan onder Grafiekgegevens."></canvas>
                    <div class="chart-tooltip" id="chart-tooltip" hidden></div>
                </div>
                <details class="chart-data">
                    <summary>Grafiekgegevens bekijken</summary>
                    <table>
                        <caption>Nieuwe accounts per maand in {{ $analytics['year'] }}</caption>
                        <thead>
                            <tr>
                                <th>Maand</th>
                                <th>Accounts</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($analytics['monthly'] as $index => $count)
                                <tr>
                                    <td>{{ $monthNames[$index] }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </details>
            </section>
            <section class="panel roles-chart-panel" aria-labelledby="role-chart-title">
                <div class="admin-panel-heading">
                    <div><span class="panel-kicker">IEDEREEN OP ZIJN PLEK</span>
                        <h2 id="role-chart-title">Verdeling per rol</h2>
                    </div><a class="icon-button" href="#rollen" aria-label="Alle rollen bekijken"><img
                            src="{{ asset('icons/tabler/arrow-up-right.svg') }}" alt="" width="19"
                            height="19"></a>
                </div>
                <div class="donut-wrap"><canvas id="roles-chart" role="img"
                        aria-label="Verdeling van gebruikers over rollen, zoals vermeld in onderstaande legenda."></canvas>
                    <div class="donut-center"><strong>{{ $stats['members'] }}</strong><span>gebruikers</span></div>
                </div>
                <ul class="role-legend">
                    @forelse($analytics['roles'] as $index => $role)
                        <li><span class="legend-dot"
                                style="--legend-color: {{ $chartColors[$index % count($chartColors)] }}"></span><span>{{ $role['label'] }}</span><strong>{{ $role['value'] }}</strong><small>{{ $stats['members'] ? round(($role['value'] / $stats['members']) * 100) : 0 }}%</small>
                    </li>@empty<li>Nog geen rollen beschikbaar.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <div class="overview-bottom" data-dashboard-section="overzicht">
            <section class="panel access-summary">
                <div class="admin-panel-heading">
                    <div><span class="panel-kicker">TOEGANG TOT HET PLATFORM</span>
                        <h2>Accountstatus</h2>
                    </div><span class="soft-icon"><img src="{{ asset('icons/tabler/shield-check.svg') }}" alt=""
                            width="22" height="22"></span>
                </div>
                <div class="access-value"><strong>{{ $analytics['activePercent'] }}<span>%</span></strong>
                    <p>van de accounts is actief</p>
                </div><progress value="{{ $stats['active'] }}" max="{{ max(1, $stats['members']) }}"
                    aria-label="{{ $stats['active'] }} van {{ $stats['members'] }} accounts actief"></progress>
                <div class="access-legend"><span><i class="legend-dot" style="--legend-color:#08a68e"></i>Actief
                        <strong>{{ $stats['active'] }}</strong></span><span><i class="legend-dot"
                            style="--legend-color:#c9dce2"></i>Overig
                        <strong>{{ $stats['members'] - $stats['active'] }}</strong></span></div>
            </section>
            <section class="panel shortcuts-panel">
                <div class="admin-panel-heading">
                    <div><span class="panel-kicker">DIRECT AAN DE SLAG</span>
                        <h2>Snel geregeld</h2>
                    </div>
                </div><a href="{{ route('beheer.members.create') }}" class="shortcut"><span class="shortcut-icon"><img
                            src="{{ asset('icons/tabler/users.svg') }}" alt="" width="20"
                            height="20"></span><span><strong>Nieuwe gebruiker</strong><small>Geef iemand
                            toegang</small></span><img src="{{ asset('icons/tabler/chevron-right.svg') }}" alt=""
                        width="16" height="16"></a><a href="#systeem" class="shortcut"><span class="shortcut-icon purple"><img
                            src="{{ asset('icons/tabler/activity.svg') }}" alt="" width="20"
                            height="20"></span><span><strong>Omgevingsstatus</strong><small>Bekijk de laatste
                            controle</small></span><img src="{{ asset('icons/tabler/chevron-right.svg') }}"
                        alt="" width="16" height="16"></a>
            </section>
        </div>
        <script type="application/json" id="admin-chart-data">{!! json_encode($analytics, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <section class="panel admin-members" id="gebruikers" data-dashboard-section="gebruikers">
            <div class="admin-panel-heading">
                <div>
                    <h2>Gebruikers beheren <span class="admin-count">{{ $members->total() }}</span></h2>
                    <p>Beheer accounts en toegang.</p>
                </div>
                <div class="admin-panel-actions"><span class="admin-live">Actuele gegevens</span><a class="admin-button"
                        href="{{ route('beheer.members.create') }}"><img src="{{ asset('icons/tabler/plus.svg') }}"
                            alt="" width="18" height="18"> Gebruiker toevoegen</a></div>
            </div>
            <form method="GET" action="{{ route('dashboard') }}#gebruikers" class="admin-filters"><input
                    type="hidden" name="year" value="{{ $analytics['year'] }}">
                <label>Zoeken<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100"
                        placeholder="Naam of e-mailadres"></label>
                <label>Rol<select name="role">
                        <option value="">Alle rollen</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->idrol }}" @selected(($filters['role'] ?? '') == $role->idrol)>{{ $role->naam }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Status<select name="status">
                        <option value="">Alle statussen</option>
                        <option value="actief" @selected(($filters['status'] ?? '') === 'actief')>Actief</option>
                        <option value="inactief" @selected(($filters['status'] ?? '') === 'inactief')>Inactief</option>
                    </select></label>
                <button class="admin-button" type="submit">Filteren</button><a class="admin-link"
                    href="{{ route('dashboard') }}">Wissen</a>
            </form>
            <div class="table-scroll">
                <table class="data-table">
                    <caption class="sr-only">Gebruikers met rol en accountstatus</caption>
                    <thead>
                        <tr>
                            <th>Gebruiker</th>
                            <th>Rol</th>
                            <th>Status</th>
                            <th>Aangemaakt</th>
                            <th>Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td>
                                    <div class="admin-person"><span
                                            class="avatar">{{ mb_strtoupper(mb_substr($member->voornaam ?? '', 0, 1) . mb_substr($member->achternaam ?? '', 0, 1)) }}</span>
                                        <div><strong>{{ $member->voornaam }} {{ $member->achternaam }}</strong>
                                            @if ($member->is(auth()->user()))
                                                <span class="admin-you">Jij</span>
                                            @endif
                                            <small>
                                                {{ $member->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="admin-role">{{ $member->rol?->naam ?? 'Geen rol' }}</span></td>
                                <td><span
                                        class="status-pill {{ strtolower($member->status ?? '') === 'actief' ? '' : 'pending' }}">{{ ucfirst($member->status ?? 'Onbekend') }}</span>
                                </td>
                                <td>{{ $member->aangemaakt_datum?->format('d-m-Y') ?? '—' }}</td>
                                <td>
                                    <div class="admin-row-actions"><a class="admin-edit"
                                            href="{{ route('beheer.members.edit', $member) }}"
                                            aria-label="Bewerk {{ $member->voornaam }} {{ $member->achternaam }}">Bewerken</a>
                                        @unless ($member->is(auth()->user()))
                                            <form method="POST" action="{{ route('beheer.members.destroy', $member) }}"
                                                onsubmit="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')">
                                                @csrf @method('DELETE')<button class="admin-delete" type="submit"
                                                    aria-label="Verwijder {{ $member->voornaam }} {{ $member->achternaam }}">Verwijderen</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty<tr>
                                <td colspan="5">
                                    <div class="admin-empty"><strong>Geen gebruikers gevonden</strong>
                                        <p>Probeer een andere zoekterm of wis je filters.</p><a
                                            href="{{ route('dashboard') }}" class="admin-link">Alle gebruikers
                                            bekijken</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="admin-pagination"><span>{{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }} van
                    {{ $members->total() }} gebruikers</span>
                <div>
                    @if ($members->previousPageUrl())
                        <a class="admin-edit" href="{{ $members->previousPageUrl() }}">← Vorige</a>
                        @endif <span>Pagina {{ $members->currentPage() }} / {{ $members->lastPage() }}</span>
                        @if ($members->nextPageUrl())
                            <a class="admin-edit" href="{{ $members->nextPageUrl() }}">Volgende →</a>
                        @endif
                </div>
            </div>
        </section>
        <section class="panel" id="rollen" data-dashboard-section="rollen">
            <div class="admin-panel-heading">
                <div>
                    <h2>Rollen & toegang</h2>
                    <p>Rollen uit je bestaande omgeving. Wijs een rol toe via het gebruikersformulier.</p>
                </div><span class="admin-count">{{ $roles->count() }} rollen</span>
            </div>
            <div class="admin-role-grid">
                @forelse($roles as $role)
                    <article class="admin-role-card"><img class="role-icon"
                            src="{{ asset('icons/tabler/shield-check.svg') }}" alt="" width="24"
                            height="24">
                        <h4>{{ $role->naam }}</h4>
                        <p>{{ $role->beschrijving ?: 'Geen beschrijving beschikbaar.' }}</p>
                        <strong>{{ $role->member_count }} gebruikers</strong>
                </article>@empty<p>Er zijn nog geen rollen ingesteld in de database.</p>
                @endforelse
            </div>
            <p class="widget-detail">Toegang wordt door de applicatie bepaald op basis van de rolnaam.</p>
        </section>
        <section class="panel" id="systeem" data-dashboard-section="systeem">
            <div class="admin-panel-heading">
                <div>
                    <h2>Omgevingsstatus</h2>
                    <p>Gecontroleerd tijdens het laden van deze pagina.</p>
                </div><span class="admin-live">Database bereikbaar</span>
            </div>
            <div class="admin-system">
                <div><span>Database</span><strong>Verbinding geslaagd</strong></div>
                <div><span>Laatste controle</span><strong>{{ now()->format('d-m-Y H:i') }}</strong></div>
                <div><span>Accountbeheer</span><strong>Alleen actieve beheerders</strong></div>
            </div>
        </section>
    </div>
@endsection

