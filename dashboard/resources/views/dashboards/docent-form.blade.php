{{-- Een formulier houdt na een validatiefout alleen zijn eigen invoer vast. --}}
@php
    $restore = old('_form') === $formId;
@endphp
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if(($method ?? 'POST') !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php
            $value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? ''));
        @endphp
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @elseif(($field['type'] ?? '') === 'datetime-local')
                @php
                    $meetingDate = $value ? substr((string) $value, 0, 10) : '';
                    $meetingTime = $value ? substr((string) $value, 11, 5) : '';
                    [$meetingHour, $meetingMinute] = array_pad(explode(':', $meetingTime), 2, '');
                @endphp
                <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-meeting-datetime>
                <span>Datum</span>
                <input id="{{ $formId }}-{{ $name }}-date" type="date" lang="nl-NL" value="{{ $meetingDate }}" data-meeting-date @required($field['required'] ?? false)>
                <span>Tijd (24-uursnotatie)</span>
                <span class="meeting-time-selects">
                    <select aria-label="Uur" data-meeting-hour @required($field['required'] ?? false)>
                        <option value="">Uur</option>
                        @for($hour = 0; $hour < 24; $hour++)
                            @php($hourValue = str_pad((string) $hour, 2, '0', STR_PAD_LEFT))
                            <option value="{{ $hourValue }}" @selected($meetingHour === $hourValue)>{{ $hourValue }}</option>
                        @endfor
                    </select>
                    <span aria-hidden="true">:</span>
                    <select aria-label="Minuut" data-meeting-minute @required($field['required'] ?? false)>
                        <option value="">Minuut</option>
                        @for($minute = 0; $minute < 60; $minute++)
                            @php($minuteValue = str_pad((string) $minute, 2, '0', STR_PAD_LEFT))
                            <option value="{{ $minuteValue }}" @selected($meetingMinute === $minuteValue)>{{ $minuteValue }}</option>
                        @endfor
                    </select>
                </span>
                <script>
                    document.currentScript.closest('form').addEventListener('submit', (event) => {
                        const form = event.currentTarget;
                        const date = form.querySelector('[data-meeting-date]').value;
                        const hour = form.querySelector('[data-meeting-hour]').value;
                        const minute = form.querySelector('[data-meeting-minute]').value;
                        const datetime = form.querySelector('[data-meeting-datetime]');
                        datetime.value = date && hour && minute ? `${date}T${hour}:${minute}` : '';
                    });
                </script>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
