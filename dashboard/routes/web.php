<?php

use App\Http\Controllers\ApplicationAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeamManagerDashboardController;
use Illuminate\Support\Facades\Route;

// Iedereen mag de startpagina bekijken. Inloggen heeft een eigen webadres.
Route::view('/', 'home')->name('home');
Route::get('/login', fn () => auth()->check() ? redirect()->route('dashboard') : view('login'))->name('login');

// Beperk het aantal pogingen om in te loggen. Dit helpt tegen het raden van wachtwoorden.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10.1,1')->name('login.attempt');

// Je moet ingelogd zijn. De controller kiest daarna de pagina voor jouw rol.
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
Route::post('/dashboard/leerdoelen', [StudentDashboardController::class, 'storeGoal'])->middleware('auth')->name('student.goals.store');
Route::put('/dashboard/leerdoelen/{goal}', [StudentDashboardController::class, 'updateGoal'])->whereNumber('goal')->middleware('auth')->name('student.goals.update');
Route::delete('/dashboard/leerdoelen/{goal}', [StudentDashboardController::class, 'destroyGoal'])->whereNumber('goal')->middleware('auth')->name('student.goals.destroy');
Route::post('/dashboard/reflecties', [StudentDashboardController::class, 'storeReflection'])->middleware('auth')->name('student.reflections.store');
Route::put('/dashboard/reflecties/{reflection}', [StudentDashboardController::class, 'updateReflection'])->whereNumber('reflection')->middleware('auth')->name('student.reflections.update');
Route::delete('/dashboard/reflecties/{reflection}', [StudentDashboardController::class, 'destroyReflection'])->whereNumber('reflection')->middleware('auth')->name('student.reflections.destroy');
Route::post('/dashboard/gesprekaanvraag', [StudentDashboardController::class, 'requestMeeting'])->middleware('auth')->name('student.meetings.request');
Route::post('/dashboard/meldingen/{notification}/lezen', [NotificationController::class, 'markRead'])
    ->whereUuid('notification')->middleware('auth')->name('notifications.read');
Route::post('/teamleider/groepen', [TeamManagerDashboardController::class, 'storeGroup'])->middleware('auth')->name('teammanager.groups.store');
Route::delete('/teamleider/groepen/{group}', [TeamManagerDashboardController::class, 'destroyGroup'])
    ->whereNumber('group')->middleware('auth')->name('teammanager.groups.destroy');
Route::post('/teamleider/groepen/{group}/leden', [TeamManagerDashboardController::class, 'storeGroupMember'])
    ->whereNumber('group')->middleware('auth')->name('teammanager.groups.members.store');
Route::delete('/teamleider/groepen/{group}/leden/{member}', [TeamManagerDashboardController::class, 'destroyGroupMember'])
    ->whereNumber('group')->whereNumber('member')->middleware('auth')->name('teammanager.groups.members.destroy');
// Afmelden is een POST-verzoek, zodat de sessie veilig wordt beëindigd.
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// Deze adressen beginnen met /beheer. De controller controleert ook of je beheerder bent.
Route::middleware('auth')->prefix('beheer')->name('beheer.')->group(function () {
    // GET opent het formulier. POST slaat een nieuwe gebruiker op.
    Route::get('/gebruikers/nieuw', [ApplicationAdminController::class, 'create'])->name('members.create');
    Route::post('/gebruikers', [ApplicationAdminController::class, 'store'])->name('members.store');
    Route::post('/rollen', [ApplicationAdminController::class, 'createRole'])->name('roles.store');
    /**
     * Open het bewerkformulier voor de gebruiker die via route model binding wordt opgehaald.
     */
    Route::get('/gebruikers/{member}/bewerken', [ApplicationAdminController::class, 'edit'])->name('members.edit');
    // {member} is het nummer van de gebruiker. PUT slaat de wijzigingen op.
    Route::post('/gebruikers/{member}/uren', [ApplicationAdminController::class, 'saveHours'])->name('members.hours.save');
    Route::post('/gebruikers/{member}/fases', [ApplicationAdminController::class, 'assignPhase'])->name('members.phases.store');
    Route::put('/gebruikers/{member}', [ApplicationAdminController::class, 'update'])->name('members.update');
    // DELETE vraagt om deze gebruiker te verwijderen. De controller controleert of dat mag.
    Route::delete('/gebruikers/{member}', [ApplicationAdminController::class, 'destroy'])->name('members.destroy');
});

// Alleen schrijfroutes voor het docentdashboard; de controller controleert rol en begeleiding.
Route::middleware('auth')->prefix('docent')->name('docent.')->controller(DocentController::class)->group(function () {
    Route::post('/studenten', 'storeStudent')->name('students.store');
    Route::delete('/studenten/{student}', 'detachStudent')->whereNumber('student')->name('students.destroy');
    Route::post('/studenten/{student}/leeruitkomsten/nieuw', 'createOutcome')->whereNumber('student')->name('outcomes.create');
    Route::post('/studenten/{student}/leeruitkomsten', 'storeOutcome')->whereNumber('student')->name('outcomes.store');
    Route::put('/studenten/{student}', 'updateStudent')->whereNumber('student')->name('students.update');
    Route::post('/notities', 'storeNote')->name('notes.store');
    Route::put('/notities/{note}', 'updateNote')->whereNumber('note')->name('notes.update');
    Route::delete('/notities/{note}', 'deleteNote')->whereNumber('note')->name('notes.destroy');
    Route::post('/gesprekken', 'storeMeeting')->name('meetings.store');
    Route::put('/gesprekken/{meeting}', 'updateMeeting')->whereNumber('meeting')->name('meetings.update');
    Route::delete('/gesprekken/{meeting}', 'deleteMeeting')->whereNumber('meeting')->name('meetings.destroy');
    Route::put('/gesprekaanvragen/{aanvraag}/goedkeuren', 'approveMeetingRequest')->whereNumber('aanvraag')->name('meeting-requests.approve');
    Route::put('/gesprekaanvragen/{aanvraag}/afkeuren', 'rejectMeetingRequest')->whereNumber('aanvraag')->name('meeting-requests.reject');
    Route::put('/leeruitkomsten/{outcome}', 'updateOutcome')->whereNumber('outcome')->name('outcomes.update');
    Route::post('/studenten/{student}/resultaten', 'storeAssignmentResult')->whereNumber('student')->name('results.store');
    Route::put('/studenten/{student}/resultaten/{result}', 'updateAssignmentResult')
        ->whereNumber('student')->whereNumber('result')->name('results.update');
    Route::delete('/studenten/{student}/resultaten/{result}', 'deleteAssignmentResult')
        ->whereNumber('student')->whereNumber('result')->name('results.destroy');
});
