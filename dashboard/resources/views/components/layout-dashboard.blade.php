{{-- Dit is de basis voor de dashboards. Elke pagina geeft hier haar eigen inhoud aan. --}}
@php
    $roleKey = $roleKey ?? auth()->user()->dashboardRoleKey();
@endphp
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ontwikkelplatform')</title>
    {{-- Laad de dashboardbestanden rechtstreeks uit public, ook zonder Vite-build. --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @if (($roleKey ?? auth()->user()?->dashboardRoleKey()) === 'beheerder')
        <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
        <script src="{{ asset('js/admin-dashboard.js') }}" defer></script>
    @endif
    <style>
        @include('components.dashboard-navigation-style')
    </style>
</head>

<body class="app-body dashboard-navigation {{ ($roleKey ?? '') === 'beheerder' ? 'admin-body' : '' }}">
    {{-- Bepaal het menu en de rolnaam voor de ingelogde gebruiker. --}}
    @php
        $roleLabels = [
            'student' => 'Student · ICT',
            'docent' => 'Docent',
            'beheerder' => 'Applicatiebeheerder',
            'teamleider' => 'Teamleider',
        ];
        $menuItems = [
            'student' => [
                ['⌂', 'Dashboard', 'all'],
                ['◉', 'Mijn ontwikkeling', 'ontwikkeling'],
                ['◷', 'Uren & resultaten', 'uren'],
                ['◇', 'Mijn leerdoelen', 'leerdoelen'],
                ['▤', 'Reflecties', 'reflecties'],
                ['☷', 'Gesprekken', 'gesprekken'],
            ],
            'docent' => [
                ['⌂', 'Dashboard', 'all'],
                ['♙', 'Studenten', 'studenten'],
                ['☷', 'Coachgesprekken', 'gesprekken'],
                ['▤', 'Notities', 'notities'],
                ['▥', 'Rapportages', 'rapportages'],
            ],
            'beheerder' => [
                ['⌂', 'Dashboard', 'all'],
                ['♙', 'Gebruikers', 'gebruikers'],
                ['⚿', 'Rollen & rechten', 'rollen'],
                ['⚙', 'Systeemstatus', 'systeem'],
            ],
            'teamleider' => [
                ['⌂', 'Dashboard', 'all'],
                ['♙', 'Studenten & groepen', 'groepen'],
                ['▥', 'Rapportages', 'rapportages'],
                ['↗', 'Trends', 'trends'],
                ['⚙', 'Instellingen', 'instellingen'],
            ],
        ];
        $currentRoleKey = $roleKey ?? 'student';
    @endphp
    {{-- Links staat het menu. Daarnaast staat de inhoud van de pagina. --}}
    <div class="dashboard-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark">IT</span>
                <span><strong>Ontwikkelplatform</strong><small>Persoonlijke groei</small></span>
            </a>
            <div class="profile-chip">
                <span
                    class="avatar">{{ strtoupper(substr(auth()->user()->voornaam ?? 'A', 0, 1) . substr(auth()->user()->achternaam ?? 'V', 0, 1)) }}</span>
                <span><strong>{{ auth()->user()->voornaam ?? 'Alex' }}
                        {{ auth()->user()->achternaam ?? 'de Vries' }}</strong><small>{{ $roleLabels[$currentRoleKey] ?? 'Gebruiker' }}</small></span>
            </div>
            {{-- Met dit menu kies je welk onderdeel je wilt zien. --}}
            <nav class="side-nav" aria-label="Hoofdnavigatie">
                @foreach ($menuItems[$currentRoleKey] ?? $menuItems['student'] as [$icon, $label, $target])
                    @if ($currentRoleKey === 'beheerder')
                        <a class="side-nav-button {{ $loop->first ? 'active' : '' }}"
                            href="{{ route('dashboard') }}#{{ $target }}" data-admin-target="{{ $target }}"
                            aria-label="{{ $label }}"
                            @if ($loop->first) aria-current="page" @endif><span>{{ $icon }}</span>
                            {{ $label }}</a>
                    @else
                        <button class="side-nav-button {{ $loop->first ? 'active' : '' }}" type="button"
                            data-dashboard-target="{{ $target }}" aria-label="{{ $label }}"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><span>{{ $icon }}</span>
                            {{ $label }}</button>
                    @endif
                @endforeach
            </nav>
            <div class="sidebar-footer">
                <strong>{{ $currentRoleKey === 'beheerder' ? 'Beheeromgeving' : 'Systeem online' }}</strong><small>Persoonlijk
                    dashboard</small></div>
        </aside>

        <div class="main-column">
            {{-- Bovenbalk met begroeting, zoeken, meldingen en uitloggen. --}}
            <header class="topbar" id="top">
                <div>
                    <h1>@yield('welcome-title', 'Goedemiddag, ' . (auth()->user()->voornaam ?? 'welkom') . '!') <span aria-hidden="true">👋</span></h1>
                    <p>@yield('welcome-subtitle', 'Bekijk hier jouw persoonlijke ontwikkeling en voortgang.')</p>
                </div>
                <div class="topbar-actions">
                    @if ($currentRoleKey !== 'beheerder')
                        <label class="search-box"><span>⌕</span><input type="search" placeholder="Zoeken..."></label>
                        <span class="notification" aria-label="Meldingen">♧<i>3</i></span>
                    @endif
                    <span
                        class="avatar avatar-light">{{ strtoupper(substr(auth()->user()->voornaam ?? 'A', 0, 1) . substr(auth()->user()->achternaam ?? 'V', 0, 1)) }}</span>
                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button class="logout-button">Uitloggen</button>
                    </form>
                </div>
            </header>

            {{-- Hier komt de inhoud van de gekozen dashboardpagina. --}}
            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>
    {{-- Zijbalkknoppen tonen alleen de gekozen sectie en laden geen aparte pagina. --}}
    @if ($currentRoleKey !== 'beheerder')
        <script>
            document.querySelectorAll('[data-dashboard-target]').forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.dataset.dashboardTarget;
                    if (!document.querySelector('[data-dashboard-section]')) {
                        window.location.href = @json(route('dashboard')) + '#' + target;
                        return;
                    }

                    const visibleTargets = target === 'leerdoelen' ?
                        ['leerdoelen', 'leeruitkomsten'] :
                        target === 'rapportages' ?
                        ['rapportages', 'trends', 'instellingen'] :
                        [target];

                    document.querySelectorAll('[data-dashboard-section]').forEach((section) => {
                        section.hidden = target !== 'all' && !visibleTargets.includes(section.dataset
                            .dashboardSection);
                    });

                    document.querySelectorAll('.content-grid').forEach((grid) => {
                        const visibleSections = Array.from(grid.children)
                            .filter((section) => !section.hidden);
                        grid.classList.toggle('single-column', visibleSections.length === 1);
                    });

                    document.querySelectorAll('[data-dashboard-target]').forEach((item) => {
                        const active = item === button;

                        item.classList.toggle('active', active);
                        item.setAttribute('aria-pressed', String(active));
                    });
                });
            });

            const initialSection = window.location.hash.slice(1);
            const initialButton = Array.from(document.querySelectorAll('[data-dashboard-target]'))
                .find(button => button.dataset.dashboardTarget === initialSection);

            if (initialButton && document.querySelector('[data-dashboard-section]')) {
                initialButton.click();
            }
        </script>
    @endif
</body>

</html>
