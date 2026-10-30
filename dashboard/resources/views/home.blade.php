{{-- Dit is de openbare startpagina. Je hoeft hiervoor niet in te loggen. --}}
@php
    // Ben je ingelogd? Ga naar het dashboard. Anders ga je naar de loginpagina.
    $workspaceUrl = auth()->check() ? route('dashboard') : route('login');
    $workspaceLabel = auth()->check() ? 'Mijn dashboard' : 'Inloggen';
    // Bewaar de teksten in lijsten. Zo kunnen we dezelfde HTML opnieuw gebruiken.
    $features = [
        ['icon' => 'layout-dashboard', 'number' => '01', 'title' => 'Overzicht dat rust geeft.', 'text' => 'Leerdoelen, reflecties en voortgang bij elkaar. Zo zie je waar je staat en wat aandacht verdient.', 'class' => 'feature-overview'],
        ['icon' => 'activity', 'number' => '02', 'title' => 'Maak je groei zichtbaar.', 'text' => 'Een plek voor je leeruitkomsten en persoonlijke doelen. Van eerste stap tot behaald resultaat.', 'class' => 'feature-growth'],
        ['icon' => 'users', 'number' => '03', 'title' => 'Samen kom je verder.', 'text' => 'Studenten, coaches en teamleiders kijken ieder vanuit hun eigen rol naar ontwikkeling.', 'class' => 'feature-together'],
    ];
    $roles = [
        ['id' => 'student', 'label' => 'Studenten', 'icon' => 'school', 'eyebrow' => 'JOUW ROUTE, JOUW GROEI', 'title' => 'Neem de regie over je ontwikkeling.', 'text' => 'Zie waar je naartoe werkt. Je persoonlijke werkruimte brengt leerdoelen, reflecties en resultaten samen.', 'points' => ['Je leerdoelen overzichtelijk bij elkaar', 'Inzicht in leeruitkomsten en voortgang', 'Ruimte voor reflectie en coachgesprekken'], 'preview' => 'Mijn ontwikkeling', 'metric' => '18 / 24', 'metricLabel' => 'leeruitkomsten behaald', 'rows' => [['Samenwerken', 'Behaald'], ['Professioneel handelen', 'In ontwikkeling'], ['Reflecteren', 'Volgende stap']]],
        ['id' => 'coach', 'label' => 'Coaches', 'icon' => 'users', 'eyebrow' => 'AANDACHT WAAR HET TELT', 'title' => 'Houd het gesprek over groei centraal.', 'text' => 'Een eigen overzicht voor begeleiding. Bekijk studenten en neem hun ontwikkeling mee in je volgende gesprek.', 'points' => ['Een overzicht van studenten', 'Coachgesprekken en notities bij elkaar', 'Voortgang als startpunt voor begeleiding'], 'preview' => 'Mijn begeleiding', 'metric' => '3', 'metricLabel' => 'onderwerpen voor je volgende gesprek', 'rows' => [['Leerdoelen bespreken', 'Voorbereiden'], ['Voortgang bekijken', 'Inzicht'], ['Afspraken vastleggen', 'Vervolg']]],
        ['id' => 'teamleider', 'label' => 'Teamleiders', 'icon' => 'building-community', 'eyebrow' => 'ZICHT OP HET GEHEEL', 'title' => 'Verbind individuele groei met je team.', 'text' => 'Breng studenten en groepen in beeld. Je teamoverzicht geeft de ontwikkeling een gezamenlijke context.', 'points' => ['Studenten en groepen in één overzicht', 'Aandacht voor rapportages en trends', 'Een eigen dashboard voor jouw rol'], 'preview' => 'Mijn teamoverzicht', 'metric' => '4', 'metricLabel' => 'groepen in dit voorbeeld', 'rows' => [['ICT 1A', 'Overzicht'], ['ICT 1B', 'Overzicht'], ['ICT 2A', 'Overzicht']]],
        ['id' => 'beheerder', 'label' => 'Beheerders', 'icon' => 'shield-check', 'eyebrow' => 'EEN GOEDE BASIS VOOR IEDEREEN', 'title' => 'Regel de toegang. Bewaar het overzicht.', 'text' => 'Beheer accounts, wijs rollen en scholen toe en bekijk de actuele verdeling van je gebruikers.', 'points' => ['Gebruikers aanmaken, zoeken en bewerken', 'Rollen, accountstatus en scholen beheren', 'Statistieken uit de bestaande database'], 'preview' => 'Applicatiebeheer', 'metric' => '4', 'metricLabel' => 'rollen, één omgeving', 'rows' => [['Gebruikers', 'Beheren'], ['Rollen & toegang', 'Organiseren'], ['Scholen', 'Verbinden']]],
    ];
    $questions = [
        ['Voor wie is DQAMA bedoeld?', 'DQAMA is een ontwikkelplatform voor studenten, coaches, teamleiders en applicatiebeheerders. Elke rol heeft een eigen dashboard met een passend overzicht.'],
        ['Hoe krijg ik een account?', 'Je applicatiebeheerder maakt je account aan en koppelt het aan een rol en eventueel een school. Heb je nog geen inloggegevens? Vraag deze aan bij je begeleider of beheerder.'],
        ['Welk dashboard zie ik na het inloggen?', 'Na het inloggen controleert het platform je rol en accountstatus. Je komt vervolgens automatisch op het dashboard dat bij jouw rol hoort.'],
        ['Zijn de voorbeelden op deze pagina echte gegevens?', 'Nee. De previews op deze pagina gebruiken voorbeeldgegevens om het platform te laten zien. Persoonlijke gegevens en beheerfuncties zijn alleen beschikbaar na het inloggen.'],
        ['Kan ik het platform op mijn telefoon openen?', 'De website past zich aan de schermgrootte van je telefoon, tablet of computer aan. Gebruik het webadres dat je school of projectgroep met je heeft gedeeld.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#102e55">
        <meta
            name="description"
            content="DQAMA brengt leerdoelen, reflecties en begeleiding samen. Ontdek een eigen werkruimte voor studenten, coaches, teamleiders en beheerders.">
        <meta property="og:title" content="DQAMA — Ruimte om te groeien">
        <meta
            property="og:description"
            content="Jouw ontwikkeling, helder in beeld. Eén platform voor studenten en hun begeleiders.">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="nl_NL">
        <title>DQAMA — Ruimte om te groeien</title>
        {{-- Laad de CSS voor het uiterlijk en JavaScript voor de knoppen en tabs. --}}
        @vite(['resources/css/landing.css', 'resources/js/landing.js'])
    </head>
    <body class="landing-body">
        <a class="lp-skip" href="#inhoud">Direct naar inhoud</a>
        <header class="lp-header" data-header>
            <div class="lp-container lp-nav-wrap">
                <a class="lp-brand" href="{{ route('home') }}" aria-label="DQAMA startpagina"><img class="lp-brand-image" src="{{ asset('images/landing/dqama-logo.png') }}" alt="DQAMA" width="150" height="86"></a>
                {{-- Deze links gaan naar onderdelen op dezelfde pagina. --}}
                <nav class="lp-nav" id="site-navigation" aria-label="Hoofdnavigatie">
                    <a href="#platform">Het platform</a><a href="#voor-wie">Voor wie</a><a href="#werkwijze">Zo werkt het</a><a href="#vragen">Veelgestelde vragen</a>
                    <a class="lp-mobile-login" href="{{ $workspaceUrl }}">{{ $workspaceLabel }} <x-landing-icon name="arrow-right" :size="17" /></a>
                </nav>
                <div class="lp-nav-actions">
                    <a class="lp-nav-login" href="{{ $workspaceUrl }}">{{ $workspaceLabel }} <x-landing-icon name="arrow-up-right" :size="17" /></a><button
                        class="lp-menu-toggle"
                        type="button"
                        aria-label="Menu openen"
                        aria-controls="site-navigation"
                        aria-expanded="false" data-menu-toggle><x-landing-icon name="menu-2" :size="24" /></button>
                </div>
            </div>
        </header>
        <main id="inhoud">
            {{-- Het eerste deel toont de uitleg, de knop om in te loggen en het logo. --}}
            <section class="lp-hero">
                <div class="lp-container lp-hero-grid">
                    <div class="lp-hero-copy">
                        <span class="lp-eyebrow"><span class="lp-live-dot"></span> JOUW AMBITIE. JOUW VOLGENDE STAP.</span>
                        <h1>
                            Kleine stappen.<br>Grote <span>groei.</span>
                        </h1>
                        <p class="lp-lead">
                            Je hebt meer in je dan je denkt. DQAMA brengt je leerdoelen, reflecties en begeleiding samen. Zodat jij ziet waar je staat — en waar je naartoe kunt.
                        </p>
                        <div class="lp-hero-actions">
                            <a class="lp-button lp-button-primary" href="{{ $workspaceUrl }}">{{ auth()->check() ? 'Naar mijn dashboard' : 'Naar mijn omgeving' }} <x-landing-icon name="arrow-right" /></a><a class="lp-text-link" href="#platform">Ontdek DQAMA <x-landing-icon name="chevron-right" :size="17" /></a>
                        </div>
                        <div class="lp-hero-footnote">
                            <span class="lp-tiny-icon"><x-landing-icon name="shield-check" :size="18" /></span>
                            <p>
                                Jouw eigen werkruimte.<br><strong>Met de juiste mensen om je heen.</strong>
                            </p>
                        </div>
                    </div>
                    {{-- Toon het logo en de achtergrond uit de map public/images/landing. --}}
                    <div class="lp-brand-stage" aria-label="DQAMA — jouw ontwikkeling in de spotlight">
                        <img
                            class="lp-stage-background"
                            src="{{ asset('images/landing/neon-stage.jpg') }}"
                            alt=""
                            width="7084"
                            height="4250"
                            fetchpriority="high">
                        <img class="lp-stage-logo" src="{{ asset('images/landing/dqama-logo.png') }}" alt="DQAMA" width="2154" height="1240">
                        <span class="lp-stage-label">JOUW TOEKOMST. IN DE SPOTLIGHT.</span>
                    </div>
                </div>
                <div class="lp-container lp-audience-strip">
                    <span>ONTWORPEN RONDOM JOU</span>
                    <div>
                        <x-landing-icon name="school" /> Studenten
                    </div>
                    <div>
                        <x-landing-icon name="users" /> Coaches
                    </div>
                    <div>
                        <x-landing-icon name="building-community" /> Teamleiders
                    </div>
                    <div>
                        <x-landing-icon name="shield-check" /> Beheerders
                    </div>
                </div>
            </section>
            <section class="lp-section lp-platform" id="platform">
                <div class="lp-container">
                    <div class="lp-section-heading">
                        <div>
                            <span class="lp-eyebrow">MINDER ZOEKEN. MEER GROEIEN.</span>
                            <h2>
                                Alles wat je verder helpt.<br><span>Op één plek.</span>
                            </h2>
                        </div>
                        <p>
                            Ontwikkeling bestaat uit meer dan een cijfer. Geef ook je doelen, inzichten en gesprekken een plek.
                        </p>
                    </div>
                    <div class="lp-feature-grid">
                        {{-- Maak voor elk item uit de lijst een kaart. --}}
                        @foreach($features as $feature)
                            <article class="lp-feature {{ $feature['class'] }}">
                                <div class="lp-feature-top">
                                    <span class="lp-feature-icon"><x-landing-icon :name="$feature['icon']" :size="25" /></span><span>{{ $feature['number'] }}</span>
                                </div>
                                <h3>
                                    {{ $feature['title'] }}
                                </h3>
                                <p>
                                    {{ $feature['text'] }}
                                </p>
                                @if($loop->first)
                                    <div class="lp-feature-bottom">
                                        <span class="lp-mini-tag"><x-landing-icon name="check" :size="13" /> Leerdoelen</span><span class="lp-mini-tag">Reflecties</span><span class="lp-mini-tag">Voortgang</span>
                                    </div>
                                @elseif($loop->iteration === 2)
                                    <div class="lp-feature-bottom lp-step-labels">
                                        <span>Ontdekken</span><x-landing-icon name="chevron-right" :size="14" /><span>Ontwikkelen</span><x-landing-icon name="chevron-right" :size="14" /><strong>Groeien</strong>
                                    </div>
                                @else
                                    <div class="lp-feature-bottom lp-collaboration">
                                        <span>S</span><span>C</span><span>T</span>
                                        <p>
                                            Ieder een rol.<br>Een gedeelde richting.
                                        </p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
            <section class="lp-section lp-roles" id="voor-wie">
                <div class="lp-container">
                    <div class="lp-section-heading lp-centered">
                        <span class="lp-eyebrow">SAMEN, IEDER VANUIT EEN EIGEN ROL</span>
                        <h2>
                            Jouw rol. <span>Jouw werkruimte.</span>
                        </h2>
                        <p>
                            Andere verantwoordelijkheden. Hetzelfde doel: ruimte maken voor ontwikkeling.
                        </p>
                    </div>
                    {{-- Deze tabs tonen voorbeelden. Ze veranderen de echte rol van de gebruiker niet. --}}
                    <div class="lp-role-tabs" aria-label="Kies een rol" data-role-tabs>
                        {{-- Maak voor elke rol een knop om het voorbeeld te kiezen. --}}
                        @foreach($roles as $role)<a href="#role-{{ $role['id'] }}" id="tab-{{ $role['id'] }}" data-role-tab="{{ $role['id'] }}" @if($loop->first) class="is-active" @endif><x-landing-icon :name="$role['icon']" :size="19" />{{ $role['label'] }}</a>@endforeach
                    </div>
                    @foreach($roles as $role)
                        <article
                                class="lp-role-panel"
                                id="role-{{ $role['id'] }}"
                                data-role-panel="{{ $role['id'] }}"
                                aria-labelledby="tab-{{ $role['id'] }}">
                            <div class="lp-role-copy">
                                <span class="lp-eyebrow">{{ $role['eyebrow'] }}</span>
                                <h3>
                                    {{ $role['title'] }}
                                </h3>
                                <p>
                                    {{ $role['text'] }}
                                </p>
                                <ul>
                                    @foreach($role['points'] as $point)
                                        <li>
                                            <span><x-landing-icon name="check" :size="14" /></span>{{ $point }}
                                        </li>
                                    @endforeach
                                </ul>
                                <a class="lp-text-link" href="{{ $workspaceUrl }}">Open jouw omgeving <x-landing-icon name="arrow-right" :size="18" /></a>
                            </div>
                            <div class="lp-role-preview">
                                <div class="lp-role-preview-header">
                                    <img
                                        class="lp-preview-logo"
                                        src="{{ asset('images/landing/dqama-logo.png') }}"
                                        alt="DQAMA"
                                        width="60"
                                        height="35"
                                        loading="lazy"><strong>{{ $role['preview'] }}</strong><span class="lp-example-label">VOORBEELD</span>
                                </div>
                                <div class="lp-role-stat">
                                    <span>{{ $role['preview'] }}</span><strong>{{ $role['metric'] }}</strong><small>{{ $role['metricLabel'] }}</small>
                                </div>
                                <div class="lp-preview-rows">
                                    @foreach($role['rows'] as [$label, $value])
                                        <div>
                                            <span class="lp-row-dot"></span><strong>{{ $label }}</strong><span>{{ $value }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="lp-preview-caption">
                                    Illustratieve weergave · geen persoonlijke gegevens
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
            {{-- Leg in drie stappen uit hoe iemand het platform gebruikt. --}}
            <section class="lp-section lp-how" id="werkwijze">
                <div class="lp-container">
                    <div class="lp-section-heading">
                        <div>
                            <span class="lp-eyebrow">EEN HELDERE START</span>
                            <h2>
                                Van inloggen<br>naar <span>vooruitkijken.</span>
                            </h2>
                        </div>
                        <p>
                            Je hoeft het niet allemaal tegelijk te weten. Begin met overzicht en bouw van daaruit verder.
                        </p>
                    </div>
                    <ol class="lp-steps">
                        <li>
                            <span class="lp-step-number">01</span>
                            <h3>
                                Open jouw omgeving
                            </h3>
                            <p>
                                Log in met het account dat je van je beheerder hebt gekregen. Je rol bepaalt jouw dashboard.
                            </p>
                        </li>
                        <li>
                            <span class="lp-step-number">02</span>
                            <h3>
                                Breng je ontwikkeling in beeld
                            </h3>
                            <p>
                                Bekijk de doelen, resultaten en onderdelen die bij jouw rol horen. Wat gaat goed? Wat vraagt aandacht?
                            </p>
                        </li>
                        <li>
                            <span class="lp-step-number">03</span>
                            <h3>
                                Kies je volgende stap
                            </h3>
                            <p>
                                Gebruik het overzicht als vertrekpunt voor een gesprek, een reflectie of een nieuw leerdoel.
                            </p>
                        </li>
                    </ol>
                </div>
            </section>
            {{-- Klik op een vraag om het antwoord te openen. Dit werkt ook zonder JavaScript. --}}
            <section class="lp-section lp-faq" id="vragen">
                <div class="lp-container lp-faq-grid">
                    <div>
                        <span class="lp-eyebrow">GOED OM TE WETEN</span>
                        <h2>
                            Nog een<br><span>vraag?</span>
                        </h2>
                        <p>
                            Hier vind je de belangrijkste antwoorden over DQAMA.
                        </p>
                        <div class="lp-help-note">
                            <x-landing-icon name="users" :size="22" />
                            <p>
                                Hulp nodig met je account?<br><strong>Je begeleider of beheerder helpt je verder.</strong>
                            </p>
                        </div>
                    </div>
                    <div class="lp-questions">
                        @foreach($questions as [$question, $answer])
                            <details class="lp-question">
                                <summary>{{ $question }}<span><x-landing-icon name="plus" :size="19" /></span></summary>
                                <p>
                                    {{ $answer }}
                                </p>
                            </details>
                        @endforeach
                    </div>
                </div>
            </section>
            <section class="lp-final-section">
                <div class="lp-container">
                    <div class="lp-final-cta">
                        <div>
                            <span class="lp-eyebrow">DE VOLGENDE STAP IS VAN JOU</span>
                            <h2>
                                Geef je ontwikkeling<br>de ruimte.
                            </h2>
                            <p>
                                Een helder overzicht. Een goed gesprek. Een stap vooruit.
                            </p>
                        </div>
                        <div class="lp-final-actions">
                            <a class="lp-button lp-button-white" href="{{ $workspaceUrl }}">{{ auth()->check() ? 'Naar mijn dashboard' : 'Log in op DQAMA' }} <x-landing-icon name="arrow-right" /></a><span>Jouw omgeving staat voor je klaar.</span>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        {{-- Onderaan staan de links en het huidige jaar. --}}
        <footer class="lp-footer">
            <div class="lp-container">
                <div class="lp-footer-top">
                    <a class="lp-brand" href="{{ route('home') }}"><img class="lp-brand-image" src="{{ asset('images/landing/dqama-logo.png') }}" alt="DQAMA" width="150" height="86"></a>
                    <p>
                        Jouw ontwikkeling, helder in beeld.
                    </p>
                    <nav aria-label="Footernavigatie">
                        <a href="#platform">Het platform</a><a href="#vragen">Vragen</a><a href="{{ $workspaceUrl }}">{{ $workspaceLabel }}</a>
                    </nav>
                </div>
                <div class="lp-footer-bottom">
                    <span>© {{ now()->year }} DQAMA</span><span>Gemaakt voor groei. Ontworpen voor mensen.</span><a href="#inhoud">Terug naar boven <x-landing-icon name="arrow-up-right" :size="14" /></a>
                </div>
            </div>
        </footer>
    </body>
</html>
