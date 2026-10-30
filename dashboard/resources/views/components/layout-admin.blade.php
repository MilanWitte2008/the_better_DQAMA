<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#073f4e">
    <title>@yield('title', 'Applicatiebeheer · ITIG')</title>
    @vite(['resources/css/app.css', 'resources/css/admin-dashboard.css', 'resources/js/admin-dashboard.js'])
</head>
<body class="beheer-body">
<a href="#main-content" class="skip-link">Direct naar inhoud</a>
<div class="beheer-shell">
    <aside class="beheer-sidebar" aria-label="Zijbalk">
        <a class="beheer-brand" href="{{ route('dashboard') }}">
            <span class="brand-icon"><img src="{{ asset('icons/tabler/layout-dashboard.svg') }}" alt="" width="27" height="27"></span>
            <span><strong>ITIG<span class="brand-dot">.</span></strong><small>Ontwikkelplatform</small></span>
        </a>
        <span class="sidebar-caption">BEHEEROMGEVING</span>
        <nav class="beheer-nav" aria-label="Hoofdnavigatie">
            @foreach([['layout-dashboard', 'Dashboard', 'all'], ['users', 'Gebruikers', 'gebruikers'], ['shield-check', 'Rollen & toegang', 'rollen'], ['activity', 'Systeemstatus', 'systeem']] as [$icon, $label, $section])
            <a href="{{ route('dashboard') }}#{{ $section }}" class="beheer-nav-link" data-admin-target="{{ $section }}" aria-label="{{ $label }}" title="{{ $label }}" @if($section === 'all') aria-current="page" @endif>
                <img src="{{ asset('icons/tabler/'.$icon.'.svg') }}" alt="" width="20" height="20"><span>{{ $label }}</span>
                <img class="nav-chevron" src="{{ asset('icons/tabler/chevron-right.svg') }}" alt="" width="14" height="14">
            </a>
            @endforeach
        </nav>
        <div class="sidebar-note"><span class="sidebar-note-icon"><img src="{{ asset('icons/tabler/shield-check.svg') }}" alt="" width="23" height="23"></span><strong>Ruimte om te groeien.</strong><p>Een fijne leeromgeving begint met goed beheer.</p><a href="{{ route('beheer.members.create') }}">Voeg een gebruiker toe <img src="{{ asset('icons/tabler/arrow-up-right.svg') }}" alt="" width="16" height="16"></a></div>
        <div class="sidebar-bottom">
            <div class="beheer-profile"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->voornaam ?? '', 0, 1).mb_substr(auth()->user()->achternaam ?? '', 0, 1)) }}</span><span><strong>{{ auth()->user()->voornaam }} {{ auth()->user()->achternaam }}</strong><small>Applicatiebeheerder</small></span></div>
            <form method="POST" action="{{ url('/logout') }}">@csrf<button class="sidebar-logout" type="submit" aria-label="Uitloggen" title="Uitloggen"><img src="{{ asset('icons/tabler/logout.svg') }}" alt="" width="18" height="18"><span>Uitloggen</span></button></form>
        </div>
    </aside>
    <div class="beheer-main">
        <header class="beheer-topbar">
            <div class="beheer-breadcrumb"><span>Werkruimte</span><img src="{{ asset('icons/tabler/chevron-right.svg') }}" alt="" width="14" height="14"><strong>Applicatiebeheer</strong></div>
            <div class="beheer-topbar-right"><span class="beheer-date"><img src="{{ asset('icons/tabler/calendar-month.svg') }}" alt="" width="17" height="17">{{ now()->locale('nl')->translatedFormat('d F Y') }}</span><span class="topbar-divider"></span><span class="workspace-badge">ITIG Workspace</span></div>
        </header>
        <main id="main-content" class="beheer-content" tabindex="-1">@yield('content')</main>
        <footer class="beheer-footer"><span>ITIG Ontwikkelplatform</span><span>Samen werken aan ontwikkeling.</span></footer>
    </div>
</div>
</body>
</html>
