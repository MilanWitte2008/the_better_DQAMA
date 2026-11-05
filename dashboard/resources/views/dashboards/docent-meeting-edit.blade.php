<details class="edit-panel" @if(old('_form') === 'meeting-'.$meeting->idgesprek) open @endif>
    <summary>Gesprek bewerken</summary>
    @include('dashboards.docent-form', ['formId' => 'meeting-'.$meeting->idgesprek, 'action' => route('docent.meetings.update', $meeting->idgesprek), 'method' => 'PUT', 'fields' => [
        'onderwerp' => ['label' => 'Onderwerp', 'value' => $meeting->onderwerp, 'required' => true],
        'datum' => ['label' => 'Datum en tijd', 'type' => 'datetime-local', 'value' => $meeting->datum ? \Illuminate\Support\Carbon::parse($meeting->datum)->format('Y-m-d\TH:i') : '', 'required' => true],
        'inhoud' => ['label' => 'Gespreksverslag', 'type' => 'textarea', 'value' => $meeting->inhoud],
        'afspraken' => ['label' => 'Afspraken', 'type' => 'textarea', 'value' => $meeting->afspraken],
    ]])
    <form method="POST" action="{{ route('docent.meetings.destroy', $meeting->idgesprek) }}" data-confirm="Dit gesprek verwijderen?">@csrf @method('DELETE')<button class="danger" type="submit">Gesprek verwijderen</button></form>
</details>
