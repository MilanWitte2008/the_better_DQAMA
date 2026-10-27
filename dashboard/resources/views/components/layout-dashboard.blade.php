<!DOCTYPE html>
<html lang="nl">
<head>
    {{-- Pagina-instellingen. --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DQAMA Dashboard')</title>
    @vite('resources/css/app.css')
</head>
<body>
    {{-- Inhoud van het dashboard. --}}
    @yield('content')
</body>
</html>
