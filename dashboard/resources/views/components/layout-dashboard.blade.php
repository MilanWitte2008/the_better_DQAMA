{{-- Gedeelde Blade-layout: alle rolpagina's vullen de titel, welkomsttekst en inhoud in. --}}
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ontwikkelplatform')</title>
    @vite(['resources/css/app.css', 'resources/css/dashboard.css'])
</head>
<body class="app-body">
    {{-- Menu en rolbenamingen verschillen per dashboardrol. --}}
    @php
        $roleKey = $roleKey ?? auth()->user()->dashboardRoleKey();
        $roleLabels = ['student' => 'Student · ICT', 'docent' => 'Docent', 'beheerder' => 'Applicatiebeheerder', 'teamleider' => 'Teamleider'];
        $menuItems = [
            'student' => [['⌂', 'Dashboard', '#top'], ['◉', 'Mijn ontwikkeling', '#ontwikkeling'], ['◷', 'Uren & resultaten', '#uren'], ['◇', 'Mijn leerdoelen', '#leerdoelen'], ['▤', 'Reflecties', '#reflecties'], ['↔', 'Vergelijken', '#vergelijken'], ['☷', 'Gesprekken', '#gesprekken']],
            'docent' => [['⌂', 'Dashboard', '#top'], ['♙', 'Studenten', '#studenten'], ['☷', 'Coachgesprekken', '#gesprekken'], ['▤', 'Notities', '#notities'], ['▥', 'Rapportages', '#rapportages']],
            'beheerder' => [['⌂', 'Dashboard', '#top'], ['♙', 'Gebruikers', '#gebruikers'], ['⚿', 'Rollen & rechten', '#rollen'], ['▦', 'Scholen', '#scholen'], ['⚙', 'Systeemstatus', '#systeem']],
            'teamleider' => [['⌂', 'Dashboard', '#top'], ['♙', 'Studenten & groepen', '#groepen'], ['▥', 'Rapportages', '#rapportages'], ['↗', 'Trends', '#trends'], ['⚙', 'Instellingen', '#instellingen']],
        ];
        $currentRoleKey = $roleKey ?? 'student';
    @endphp
    {{-- Hoofdomhulsel met vaste zijbalk en het centrale paginagedeelte. --}}
    <div class="dashboard-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark">IT</span>
                <span><strong>Ontwikkelplatform</strong><small>Persoonlijke groei</small></span>
            </a>
            <div class="profile-chip">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->voornaam ?? 'A', 0, 1) . substr(auth()->user()->achternaam ?? 'V', 0, 1)) }}</span>
                <span><strong>{{ auth()->user()->voornaam ?? 'Alex' }} {{ auth()->user()->achternaam ?? 'de Vries' }}</strong><small>{{ $roleLabels[$currentRoleKey] ?? 'Gebruiker' }}</small></span>
            </div>
            <nav class="side-nav" aria-label="Hoofdnavigatie">
                @foreach (($menuItems[$currentRoleKey] ?? $menuItems['student']) as [$icon, $label, $anchor])
                    <a class="{{ $loop->first ? 'active' : '' }}" href="{{ $loop->first ? route('dashboard') : $anchor }}"><span>{{ $icon }}</span> {{ $label }}</a>
                @endforeach
            </nav>
            <div class="sidebar-footer"><strong>Systeem online</strong><small>Persoonlijk dashboard</small></div>
        </aside>

        <div class="main-column">
            <header class="topbar" id="top">
                <div>
                    <h1>@yield('welcome-title', 'Goedemiddag, ' . (auth()->user()->voornaam ?? 'welkom') . '!') <span aria-hidden="true">👋</span></h1>
                    <p>@yield('welcome-subtitle', 'Bekijk hier jouw persoonlijke ontwikkeling en voortgang.')</p>
                </div>
                <div class="topbar-actions">
                    <label class="search-box"><span>⌕</span><input type="search" placeholder="Zoeken..."></label>
                    <span class="notification" aria-label="Meldingen">♧<i>3</i></span>
                    <span class="avatar avatar-light">{{ strtoupper(substr(auth()->user()->voornaam ?? 'A', 0, 1) . substr(auth()->user()->achternaam ?? 'V', 0, 1)) }}</span>
                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button class="logout-button">Uitloggen</button>
                    </form>
                </div>
            </header>

            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
