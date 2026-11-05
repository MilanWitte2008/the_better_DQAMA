@if($coachLinked)
<details class="edit-panel" id="student-toevoegen" @if(old('_form') === 'student-new') open @endif>
    <summary class="create-student-button">+ Student toevoegen</summary>
    <p class="footnote">Maak een studentaccount aan en koppel het direct aan jouw begeleiding. De student kan inloggen met dit e-mailadres en wachtwoord. Een bestaand account koppelen verloopt via de beheerder.</p>
    @include('dashboards.docent-form', ['formId' => 'student-new', 'action' => route('docent.students.store'), 'method' => 'POST', 'fields' => [
        'voornaam' => ['label' => 'Voornaam', 'max' => 50, 'required' => true],
        'achternaam' => ['label' => 'Achternaam', 'max' => 50, 'required' => true],
        'email' => ['label' => 'E-mailadres', 'type' => 'email', 'max' => 100, 'required' => true],
        'password' => ['label' => 'Wachtwoord (minimaal 8 tekens)', 'type' => 'password', 'max' => 72, 'required' => true],
        'password_confirmation' => ['label' => 'Herhaal wachtwoord', 'type' => 'password', 'max' => 72, 'required' => true],
        'studentnummer' => ['label' => 'Studentnummer (optioneel)', 'max' => 20],
        'klas' => ['label' => 'Klas (optioneel)', 'max' => 20],
        'opleiding' => ['label' => 'Opleiding (optioneel)', 'max' => 50],
    ]])
</details>
@endif
