<?php

declare(strict_types=1);

// Start de PHP-sessie zodat we loginstatus en CSRF-token kunnen bewaren.
session_start();

// Laad de databaseverbinding uit config.php.
require_once __DIR__ . '/config.php';

// Maak een beveiligingstoken aan voor het loginformulier als die nog niet bestaat.
if (!isset($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

// Deze variabelen worden later gebruikt om foutmeldingen en de ingevulde gebruikersnaam te tonen.
$error = '';
$username = '';

// Koppel elke gebruikersrol aan de juiste pagina na een succesvolle login.
$rolePages = [
    'student' => 'student.php',
    'coach' => 'docent.php',
    'docent' => 'docent.php',
    'teammanager' => 'teammanager.php',
    'manager' => 'teammanager.php',
    'admin' => 'aplicatiebeheer.php',
    'applicatiebeheerder' => 'aplicatiebeheer.php',
    'beheerder' => 'aplicatiebeheer.php',
];

// Verwerk het loginformulier alleen wanneer de gebruiker het formulier verstuurt.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lees en normaliseer de formulierwaarden.
    $username = trim((string) ($_POST['username'] ?? ''));
    $usernameLower = strtolower($username);
    $password = (string) ($_POST['password'] ?? '');
    $token = (string) ($_POST['_token'] ?? '');

    // Controleer eerst of het formulier met het juiste sessietoken is verstuurd.
    if (!hash_equals((string) $_SESSION['_token'], $token)) {
        $error = 'De sessie is verlopen. Probeer opnieuw in te loggen.';
    } elseif ($username === '' || $password === '') {
        // Beide velden zijn verplicht.
        $error = 'Vul je gebruiker en wachtwoord in.';
    } else {
        // Controleer of config.php een geldige PDO-databaseverbinding heeft gemaakt.
        if (!isset($conn) || !$conn instanceof PDO) {
            $error = 'Er is geen databaseverbinding beschikbaar.';
        } else {
            // Zoek de gebruiker op basis van e-mailadres of voornaam.
            $statement = $conn->prepare(
                'SELECT lid.idlid, lid.voornaam, lid.achternaam, lid.email, lid.wachtwoord, lid.status, rol.naam AS rol
                 FROM lid
                 INNER JOIN rol ON rol.idrol = lid.rol_idrol
                 WHERE (lid.email = :username OR LOWER(lid.voornaam) = :username_lower)
                 LIMIT 1'
            );
            $statement->execute([
                'username' => $username,
                'username_lower' => $usernameLower,
            ]);
            $user = $statement->fetch();

            // Controleer het wachtwoord: eerst gehasht, daarna tijdelijk ook als platte tekst voor bestaande data.
            $passwordMatches = false;

            if ($user !== false) {
                $storedPassword = (string) $user['wachtwoord'];
                $passwordMatches = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);
            }

            // Toon duidelijke foutmeldingen bij ongeldige gegevens of een inactief account.
            if ($user === false || !$passwordMatches) {
                $error = 'De combinatie van gebruiker en wachtwoord klopt niet.';
            } elseif (strtolower((string) $user['status']) !== 'actief') {
                $error = 'Dit account is niet actief.';
            } else {
                // Bepaal de juiste dashboardpagina voor de rol van deze gebruiker.
                $role = strtolower((string) $user['rol']);
                $targetPage = $rolePages[$role] ?? 'student.php';

                // Sla de ingelogde gebruiker op in de sessie.
                $_SESSION['user'] = [
                    'id' => (int) $user['idlid'],
                    'name' => trim($user['voornaam'] . ' ' . $user['achternaam']),
                    'email' => $user['email'],
                    'role' => $role,
                ];

                // Stuur de gebruiker door naar zijn/haar dashboard.
                header('Location: ' . $targetPage);
                exit;
            }
        }
    }
}

// Geef serverdata veilig door aan React, vergelijkbaar met hoe Laravel data aan Blade/Inertia geeft.
$appState = [
    'csrfToken' => $_SESSION['_token'],
    'error' => $error,
    'username' => $username,
    'loginAction' => 'index.php',
    'framework' => 'Laravel-ready PHP backend met React frontend',
];
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Inloggen - Ontwikkelplatform</title>
<link rel="stylesheet" href="style.css">
<script>
  // Maak de PHP-data beschikbaar in JavaScript voor de React-app.
  window.Laravel = <?= json_encode($appState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
</head>
<body class="login-page">
  <!-- Hier wordt de React-app in geladen. -->
  <div id="app"></div>

  <!-- Fallback wanneer JavaScript uit staat. -->
  <noscript>
    <div class="login-wrap">
      <section class="login-box">
        <h2>JavaScript is nodig</h2>
        <p class="intro">Zet JavaScript aan om in te loggen op het ontwikkelplatform.</p>
      </section>
    </div>
  </noscript>

  <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
  <script type="text/babel">
    // Lees de data die PHP bovenaan de pagina heeft klaargezet.
    const appState = window.Laravel || {};

    // React-component voor de volledige loginpagina.
    function LoginPage() {
      // Houd bij of het wachtwoord zichtbaar of verborgen is.
      const [showPassword, setShowPassword] = React.useState(false);

      return (
        <main className="login-wrap">
          {/* Linkerkant met uitleg over het platform. */}
          <section className="login-info">
            <div className="login-brand">
              <div className="login-logo">O</div>
              <div>
                <strong>Ontwikkelplatform</strong>
                <small>Persoonlijke ontwikkeling</small>
              </div>
            </div>

            <h1>Inzicht in jouw ontwikkeling.</h1>
            <p>Bekijk leeruitkomsten, voortgang, gevalideerde uren en leerdoelen vanuit een overzichtelijke omgeving.</p>

            <div className="feature"><b>&check;</b><span>Persoonlijke ontwikkeling in drie fases</span></div>
            <div className="feature"><b>&check;</b><span>Betrouwbare gegevens uit externe bronnen</span></div>
            <div className="feature"><b>&check;</b><span>Inzicht voor studenten, coaches en teams</span></div>
          </section>

          {/* Rechterkant met het loginformulier. */}
          <section className="login-box">
            <h2>Welkom terug</h2>
            <p className="intro">Log in om verder te gaan naar het ontwikkelplatform.</p>

            {appState.error ? (
              <div className="login-error" role="alert">{appState.error}</div>
            ) : null}

            {/* Het formulier post gewoon naar PHP; React verzorgt alleen de interface. */}
            <form method="post" action={appState.loginAction || "index.php"} autoComplete="on">
              {/* CSRF-token beschermt tegen ongewenst verstuurde formulieren. */}
              <input type="hidden" name="_token" value={appState.csrfToken || ""} />

              <label htmlFor="username">Gebruiker</label>
              <input
                id="username"
                name="username"
                type="text"
                placeholder="Vul je email in"
                defaultValue={appState.username || ""}
                required
              />

              <label htmlFor="password">Wachtwoord</label>
              <div className="password-row">
                <input
                  id="password"
                  name="password"
                  type={showPassword ? "text" : "password"}
                  placeholder="Vul je wachtwoord in"
                  required
                />
                <button
                  className="eye"
                  type="button"
                  aria-label={showPassword ? "Verberg wachtwoord" : "Toon wachtwoord"}
                  onClick={() => setShowPassword((visible) => !visible)}
                >
                  {showPassword ? "x" : "o"}
                </button>
              </div>

              <div className="login-options">
                <label className="remember"><input name="remember" type="checkbox" /> Ingelogd blijven</label>
              </div>

              <button className="login-btn" type="submit">Inloggen &rarr;</button>
            </form>

            <div className="security">Veilige toegang &middot; Alleen geautoriseerde gebruikers</div>
          </section>
        </main>
      );
    }

    // Start React en render de loginpagina in de div met id="app".
    ReactDOM.createRoot(document.getElementById("app")).render(<LoginPage />);
  </script>
</body>
</html>
