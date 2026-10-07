<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;

if (!is_array($user)) {
    header('Location: index.php');
    exit;
}

$role = strtolower((string) ($user['role'] ?? ''));
$roleAliases = [
    'student' => 'student',
    'coach' => 'coach',
    'docent' => 'coach',
    'teammanager' => 'teammanager',
    'manager' => 'teammanager',
    'admin' => 'admin',
    'applicatiebeheerder' => 'admin',
    'beheerder' => 'admin',
];
$role = $roleAliases[$role] ?? null;

if ($role === null) {
    http_response_code(403);
    exit('Geen toegang tot deze pagina.');
}

$roleConfig = [
    'student' => [
        'brand' => 'ITIG',
        'brandMark' => 'IT',
        'tagline' => 'Persoonlijke ontwikkeling',
        'roleLabel' => 'Student · ICT',
        'avatarClass' => 'student-avatar',
        'items' => [
            ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
            ['page' => 'ontwikkeling', 'label' => 'Mijn ontwikkeling'],
            ['page' => 'uren', 'label' => 'Uren & resultaten'],
            ['page' => 'leerdoelen', 'label' => 'Mijn leerdoelen'],
            ['page' => 'reflecties', 'label' => 'Reflecties'],
            ['page' => 'vergelijken', 'label' => 'Vergelijken'],
            ['page' => 'gesprekken', 'label' => 'Gesprekken'],
        ],
    ],
    'coach' => [
        'brand' => 'Ontwikkelplatform',
        'brandMark' => 'O',
        'tagline' => 'Persoonlijke groei',
        'roleLabel' => 'Studentcoach',
        'avatarClass' => 'coach-avatar',
        'items' => [
            ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
            ['page' => 'studenten', 'label' => 'Studenten'],
            ['page' => 'coachgesprekken', 'label' => 'Coachgesprekken'],
            ['page' => 'notities', 'label' => 'Notities'],
            ['page' => 'rapportages', 'label' => 'Rapportages'],
            ['page' => 'instellingen', 'label' => 'Instellingen'],
        ],
    ],
    'teammanager' => [
        'brand' => 'Ontwikkelplatform',
        'brandMark' => 'O',
        'tagline' => 'Persoonlijke groei',
        'roleLabel' => 'Teammanager',
        'avatarClass' => 'manager-avatar',
        'items' => [
            ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
            ['page' => 'studenten', 'label' => 'Studenten & groepen'],
            ['page' => 'rapportages', 'label' => 'Rapportages'],
            ['page' => 'trends', 'label' => 'Trends'],
            ['page' => 'rapportage', 'label' => 'Rapportage'],
            ['page' => 'instellingen', 'label' => 'Instellingen'],
        ],
    ],
    'admin' => [
        'brand' => 'Ontwikkelplatform',
        'brandMark' => 'O',
        'tagline' => 'Beheeromgeving',
        'roleLabel' => 'Applicatiebeheerder',
        'avatarClass' => 'admin-avatar',
        'items' => [
            ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
            ['page' => 'gebruikers', 'label' => 'Gebruikers'],
            ['page' => 'rollen', 'label' => 'Rollen & rechten'],
            ['page' => 'instellingen', 'label' => 'Instellingen'],
        ],
    ],
];

$config = $roleConfig[$role];
$dashboardFile = [
    'student' => 'student.php',
    'coach' => 'docent.php',
    'teammanager' => 'teammanager.php',
    'admin' => 'aplicatiebeheer.php',
][$role];
$currentPage = (string) ($_GET['page'] ?? 'dashboard');
$pageBySlug = [];

foreach ($config['items'] as $item) {
    $pageBySlug[$item['page']] = $item;
}

if (!isset($pageBySlug[$currentPage])) {
    http_response_code(404);
    $currentPage = 'dashboard';
}

$sidebarItems = [];
foreach ($config['items'] as $item) {
    $item['href'] = $dashboardFile . '?page=' . rawurlencode($item['page']);
    $sidebarItems[] = $item;
}

$name = trim((string) ($user['name'] ?? 'Gebruiker'));
$nameParts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
$initials = '';
foreach (array_slice($nameParts, 0, 2) as $part) {
    preg_match('/^\X/u', $part, $firstCharacter);
    $initials .= $firstCharacter[0] ?? '';
}

$sidebarData = [
    'brand' => $config['brand'],
    'brandMark' => $config['brandMark'],
    'tagline' => $config['tagline'],
    'name' => $name !== '' ? $name : 'Gebruiker',
    'initials' => strtoupper($initials !== '' ? $initials : 'G'),
    'roleLabel' => $config['roleLabel'],
    'avatarClass' => $config['avatarClass'],
    'activePage' => $currentPage,
    'items' => $sidebarItems,
];
$activeItem = $pageBySlug[$currentPage] ?? $pageBySlug['dashboard'];
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $escape($activeItem['label']) ?> - Ontwikkelplatform</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page sidebar-<?= $escape($role) ?>">
  <div class="screen">
    <header class="screenbar">
      <b><?= $escape($activeItem['label']) ?></b>
      <div class="screen-actions">
        <span class="avatar <?= $escape($config['avatarClass']) ?>"><?= $escape($sidebarData['initials']) ?></span>
        <span><?= $escape($sidebarData['name']) ?></span>
      </div>
    </header>
    <div class="screenbody">
      <?php require __DIR__ . '/sidebar.php'; ?>
      <main class="main">
        <h2><?= $escape($activeItem['label']) ?></h2>
        <?php if ($currentPage === 'dashboard'): ?>
          <small>Welkom terug, <?= $escape($sidebarData['name']) ?>.</small>
        <?php endif; ?>
      </main>
    </div>
  </div>
</body>
</html>
