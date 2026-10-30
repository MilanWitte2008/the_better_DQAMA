<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b2942">
    <title>Inloggen | DQAMA</title>
    @vite('resources/css/app.css')
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-intro" aria-labelledby="welcome-title">
            <a class="login-brand" href="{{ route('home') }}" aria-label="DQAMA startpagina">
                <span class="login-brand-mark" aria-hidden="true">D</span>
                <span>
                    <strong>DQAMA</strong>
                    <small>Ontwikkeling in beeld</small>
                </span>
            </a>

            <div class="intro-copy">
                <p class="intro-eyebrow">Jouw ontwikkeling, helder in beeld</p>
                <h1 id="welcome-title">Ruimte om te groeien.</h1>
                <p>Een overzichtelijke plek voor reflectie, leerdoelen en gesprekken. Log in en ga verder met jouw ontwikkeling.</p>

            </div>

            <p class="intro-footer">Een stap tegelijk. Met zicht op vooruitgang.</p>
            <span class="intro-decoration intro-decoration-one" aria-hidden="true"></span>
            <span class="intro-decoration intro-decoration-two" aria-hidden="true"></span>
        </section>

        <section class="login-form-side" aria-labelledby="login-title">
            <div class="login-form-wrap">
                <div class="login-mobile-brand" aria-hidden="true">
                    <span class="login-brand-mark">D</span>
                    <strong>DQAMA</strong>
                </div>

                <p class="form-eyebrow">Welkom terug</p>
                <h2 id="login-title">Log in op je account</h2>
                <p class="login-subtitle">Vul je gegevens in om verder te gaan.</p>

                @if (session('login_error') || $errors->any())
                    <div class="login-error" role="alert">
                        <strong>Het e-mailadres of wachtwoord is verkeerd.</strong>
                        @if (session('login_error'))
                            <span>{{ session('login_error') }}</span>
                        @else
                            @forelse ($errors->all() as $message)
                            <span>{{ $message }}</span>
                            @empty
                                <span>Controleer je e-mailadres en wachtwoord en probeer het opnieuw.</span>
                            @endforelse
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="login-form">
                    @csrf

                    <label for="email">E-mailadres</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="naam@voorbeeld.nl"
                        autocomplete="username"
                        inputmode="email"
                        required
                        autofocus
                        @if ($errors->has('email')) aria-describedby="email-error" @endif
                    >
                    @error('email')
                        <p id="email-error" class="field-error">{{ $message }}</p>
                    @enderror

                    <div class="password-label-row">
                        <label for="password">Wachtwoord</label>
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Vul je wachtwoord in"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <p id="password-error" class="field-error">{{ $message }}</p>
                    @enderror

                    <button class="login-submit" type="submit">
                        <span>Inloggen</span>
                        <span class="submit-arrow" aria-hidden="true">&#8594;</span>
                    </button>
                </form>

                <p class="secure-note"><span aria-hidden="true">&#9679;</span> Je gegevens worden veilig verwerkt</p>
            </div>
        </section>
    </main>
</body>
</html>

