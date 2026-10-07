<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
if (!is_array($user)) {
    header('Location: index.php');
    exit;
}

$userRole = strtolower((string) ($user['role'] ?? ''));
if (!in_array($userRole, ['coach', 'docent'], true)) {
    http_response_code(403);
    exit('Geen toegang tot de docentpagina.');
}

$page = (string) ($_GET['page'] ?? 'dashboard');
$selectedStudentId = filter_var($_GET['student'] ?? null, FILTER_VALIDATE_INT);
$selectedStudentId = $selectedStudentId === false ? null : $selectedStudentId;
$message = (string) ($_SESSION['coach_message'] ?? '');
unset($_SESSION['coach_message']);
$error = '';
$coachId = null;
$students = [];
$selectedStudent = null;
$outcomes = [];
$notes = [];
$meetings = [];
$goals = [];
$phases = [];

if (!isset($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

if (!isset($conn) || !$conn instanceof PDO) {
    $error = 'Er is geen databaseverbinding beschikbaar. Controleer de database-instellingen.';
} else {
    $coachStatement = $conn->prepare('SELECT idcoach FROM coach WHERE lid_idlid = :member_id LIMIT 1');
    $coachStatement->execute(['member_id' => (int) ($user['id'] ?? 0)]);
    $coachValue = $coachStatement->fetchColumn();

    if ($coachValue === false) {
        $error = 'Er is geen coachprofiel gekoppeld aan dit account.';
    } else {
        $coachId = (int) $coachValue;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $token = (string) ($_POST['_token'] ?? '');
            $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
            $studentId = $studentId === false ? null : $studentId;
            $formAction = (string) ($_POST['form_action'] ?? '');
            $returnPage = (string) ($_POST['return_page'] ?? 'studenten');
            $returnPage = in_array($returnPage, ['dashboard', 'studenten', 'coachgesprekken', 'notities'], true)
                ? $returnPage
                : 'studenten';

            if (!hash_equals((string) $_SESSION['_token'], $token)) {
                $error = 'De sessie is verlopen. Vernieuw de pagina en probeer het opnieuw.';
            } elseif ($studentId === null) {
                $error = 'Selecteer een geldige student.';
            } else {
                $assignedStudent = $conn->prepare(
                    'SELECT student_idstudent
                     FROM student_coach
                     WHERE student_idstudent = :student_id
                       AND coach_idcoach = :coach_id
                       AND status = :status
                     LIMIT 1'
                );
                $assignedStudent->execute([
                    'student_id' => $studentId,
                    'coach_id' => $coachId,
                    'status' => 'actief',
                ]);

                if ($assignedStudent->fetchColumn() === false) {
                    $error = 'Je kunt alleen notities en gesprekken opslaan voor een toegewezen student.';
                } elseif ($formAction === 'save-note') {
                    $note = trim((string) ($_POST['note'] ?? ''));

                    if ($note === '') {
                        $error = 'Schrijf eerst een coachnotitie.';
                    } else {
                        $saveNote = $conn->prepare(
                            'INSERT INTO begeleidingsnotitie (inhoud, datum, type, idcoach, idstudent)
                             VALUES (:content, :date, :type, :coach_id, :student_id)'
                        );
                        $saveNote->execute([
                            'content' => $note,
                            'date' => date('Y-m-d H:i:s'),
                            'type' => 'notitie',
                            'coach_id' => $coachId,
                            'student_id' => $studentId,
                        ]);
                        $_SESSION['coach_message'] = 'De coachnotitie is opgeslagen.';
                        header('Location: docent.php?page=' . rawurlencode($returnPage) . '&student=' . $studentId);
                        exit;
                    }
                } elseif ($formAction === 'save-meeting') {
                    $meetingDate = trim((string) ($_POST['meeting_date'] ?? ''));
                    $subject = trim((string) ($_POST['subject'] ?? ''));
                    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $meetingDate);

                    if ($date === false || $date->format('Y-m-d') !== $meetingDate) {
                        $error = 'Vul een geldige datum voor het gesprek in.';
                    } elseif ($subject === '') {
                        $error = 'Vul een onderwerp voor het gesprek in.';
                    } else {
                        $saveMeeting = $conn->prepare(
                            'INSERT INTO gesprekken (datum, onderwerp, student_idstudent)
                             VALUES (:date, :subject, :student_id)'
                        );
                        $saveMeeting->execute([
                            'date' => $date->format('Y-m-d 00:00:00'),
                            'subject' => $subject,
                            'student_id' => $studentId,
                        ]);
                        $_SESSION['coach_message'] = 'Het coachgesprek is opgeslagen.';
                        header('Location: docent.php?page=coachgesprekken&student=' . $studentId);
                        exit;
                    }
                } else {
                    $error = 'De aangevraagde actie is niet beschikbaar.';
                }

                $selectedStudentId = $studentId;
            }
        }

        $studentStatement = $conn->prepare(
            "SELECT s.idstudent, s.studentnummer, s.klas, s.opleiding,
                    l.voornaam, l.achternaam,
                    COUNT(sl.id_leeruitkomsten) AS outcome_count,
                    COALESCE(AVG(
                        CASE
                            WHEN LOWER(sl.status) = 'behaald' THEN 100
                            WHEN sl.vooruitgang IS NOT NULL THEN sl.vooruitgang
                            WHEN LOWER(sl.status) = 'bezig' THEN 60
                            ELSE 0
                        END
                    ), 0) AS progress
             FROM student_coach sc
             INNER JOIN student s ON s.idstudent = sc.student_idstudent
             INNER JOIN lid l ON l.idlid = s.lid_idlid
             LEFT JOIN student_leeruitkomsten sl ON sl.student_idstudent = s.idstudent
             WHERE sc.coach_idcoach = :coach_id AND sc.status = :status
             GROUP BY s.idstudent, s.studentnummer, s.klas, s.opleiding, l.voornaam, l.achternaam
             ORDER BY l.achternaam, l.voornaam"
        );
        $studentStatement->execute(['coach_id' => $coachId, 'status' => 'actief']);
        $students = $studentStatement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($students as &$student) {
            $student['name'] = trim((string) ($student['voornaam'] ?? '') . ' ' . (string) ($student['achternaam'] ?? ''));
            $student['progress'] = (int) round((float) $student['progress']);
            $student['outcome_count'] = (int) $student['outcome_count'];
            $student['initials'] = '';
            foreach (array_slice(preg_split('/\s+/u', trim($student['name'])) ?: [], 0, 2) as $part) {
                preg_match('/^\X/u', $part, $firstCharacter);
                $student['initials'] .= $firstCharacter[0] ?? '';
            }
            $student['initials'] = strtoupper($student['initials'] ?: 'S');
        }
        unset($student);

        if ($selectedStudentId === null && $students !== []) {
            $selectedStudentId = (int) $students[0]['idstudent'];
        }

        foreach ($students as $student) {
            if ((int) $student['idstudent'] === $selectedStudentId) {
                $selectedStudent = $student;
                break;
            }
        }

        if ($selectedStudentId !== null && $selectedStudent === null && $students !== []) {
            http_response_code(404);
            $selectedStudentId = (int) $students[0]['idstudent'];
            $selectedStudent = $students[0];
        }

        if ($selectedStudent !== null) {
            $outcomeStatement = $conn->prepare(
                'SELECT lo.titel, lo.code, f.naam AS phase_name, sl.status, sl.vooruitgang, sl.laatste_update
                 FROM student_leeruitkomsten sl
                 INNER JOIN leeruitkomsten lo ON lo.leeruitkomsten_id = sl.leeruitkomsten_leeruitkomsten_id
                 LEFT JOIN fase f ON f.idfase = lo.fase_idfase
                 WHERE sl.student_idstudent = :student_id
                 ORDER BY f.volgorde, lo.titel'
            );
            $outcomeStatement->execute(['student_id' => $selectedStudentId]);
            $outcomes = $outcomeStatement->fetchAll(PDO::FETCH_ASSOC);

            $noteStatement = $conn->prepare(
                'SELECT inhoud, datum, type
                 FROM begeleidingsnotitie
                 WHERE idcoach = :coach_id AND idstudent = :student_id
                 ORDER BY datum DESC'
            );
            $noteStatement->execute(['coach_id' => $coachId, 'student_id' => $selectedStudentId]);
            $notes = $noteStatement->fetchAll(PDO::FETCH_ASSOC);

            $meetingStatement = $conn->prepare(
                'SELECT datum, onderwerp, inhoud, afspraken
                 FROM gesprekken
                 WHERE student_idstudent = :student_id
                 ORDER BY datum DESC'
            );
            $meetingStatement->execute(['student_id' => $selectedStudentId]);
            $meetings = $meetingStatement->fetchAll(PDO::FETCH_ASSOC);

            $goalStatement = $conn->prepare(
                'SELECT titel, omschrijving, status, einddatum
                 FROM leerdoelen
                 WHERE student_idstudent = :student_id
                 ORDER BY einddatum, titel'
            );
            $goalStatement->execute(['student_id' => $selectedStudentId]);
            $goals = $goalStatement->fetchAll(PDO::FETCH_ASSOC);

            $phaseStatement = $conn->prepare(
                'SELECT f.naam,
                        AVG(
                            CASE
                                WHEN LOWER(sl.status) = \'behaald\' THEN 100
                                WHEN sl.vooruitgang IS NOT NULL THEN sl.vooruitgang
                                WHEN LOWER(sl.status) = \'bezig\' THEN 60
                                ELSE 0
                            END
                        ) AS progress
                 FROM fase f
                 LEFT JOIN leeruitkomsten lo ON lo.fase_idfase = f.idfase
                 LEFT JOIN student_leeruitkomsten sl
                   ON sl.leeruitkomsten_leeruitkomsten_id = lo.leeruitkomsten_id
                  AND sl.student_idstudent = :student_id
                 GROUP BY f.idfase, f.naam, f.volgorde
                 ORDER BY f.volgorde
                 LIMIT 3'
            );
            $phaseStatement->execute(['student_id' => $selectedStudentId]);
            $phases = $phaseStatement->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$educationFilter = trim((string) ($_GET['opleiding'] ?? ''));
$visibleStudents = array_values(array_filter(
    $students,
    static fn (array $student): bool =>
        ($search === ''
            || stripos($student['name'], $search) !== false
            || stripos((string) $student['studentnummer'], $search) !== false)
        && ($educationFilter === '' || $student['opleiding'] === $educationFilter)
));
$educations = array_values(array_unique(array_filter(array_column($students, 'opleiding'))));
$activeSection = (string) ($_GET['section'] ?? 'overzicht');
$allowedSections = ['overzicht', 'leeruitkomsten', 'doelen', 'gesprekken', 'notities'];
if (!in_array($activeSection, $allowedSections, true)) {
    $activeSection = 'overzicht';
}

$coachPageData = [
    'page' => $page,
    'name' => (string) ($user['name'] ?? 'Studentcoach'),
    'initials' => strtoupper(implode('', array_map(
        static fn (string $part): string => mb_substr($part, 0, 1),
        array_slice(preg_split('/\s+/u', trim((string) ($user['name'] ?? ''))) ?: [], 0, 2)
    )) ?: 'SC'),
    'search' => $search,
    'educationFilter' => $educationFilter,
    'educations' => $educations,
    'students' => $visibleStudents,
    'totalStudents' => count($students),
    'selectedStudent' => $selectedStudent,
    'activeSection' => $activeSection,
    'outcomes' => $outcomes,
    'notes' => $notes,
    'meetings' => $meetings,
    'goals' => $goals,
    'phases' => $phases,
    'message' => $message,
    'error' => $error,
    'csrfToken' => $_SESSION['_token'],
    'today' => date('Y-m-d'),
];

require __DIR__ . '/dashboard-layout.php';
