{{-- Inlogpagina. --}}
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen | DQAMA</title>
    @vite('resources/css/app.css')
</head>
<body>
    {{-- Formulier om in te loggen. --}}
    <main class="mx-auto mt-20 max-w-sm rounded bg-white p-6 shadow">
        <h1 class="mb-4 text-2xl font-bold">Inloggen</h1>

        <form method="POST" action="/login" class="space-y-4">
            {{-- Beveiligingstoken. --}}
            @csrf
            <div>
                <label for="email">E-mailadres</label>
                <input id="email" name="email" type="email" required class="w-full rounded border p-2">
                @error('email') <p class="text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password">Wachtwoord</label>
                {{-- Wachtwoordveld. --}}
                <input id="password" name="password" type="password" required class="w-full rounded border p-2">
            </div>
            <button class="w-full rounded bg-indigo-600 p-2 text-white">Inloggen</button>
        </form>
    </main>
</body>
</html>
