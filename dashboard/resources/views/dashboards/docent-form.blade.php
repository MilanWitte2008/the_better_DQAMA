{{-- Een formulier houdt na een validatiefout alleen zijn eigen invoer vast. --}}
@php($restore = old('_form') === $formId)
<form class="docent-form" action="{{ $action }}" method="POST">
    @csrf
    @if(($method ?? 'POST') !== 'POST') @method($method) @endif
    <input type="hidden" name="_form" value="{{ $formId }}">
    @foreach($fields as $name => $field)
        @php($value = ($field['type'] ?? '') === 'password' ? '' : ($restore ? old($name) : ($field['value'] ?? '')))
        <label for="{{ $formId }}-{{ $name }}">{{ $field['label'] }}
            @if(($field['type'] ?? '') === 'select')
                <select id="{{ $formId }}-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false)>
                    @foreach($field['options'] as $key => $label)<option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>@endforeach
                </select>
            @elseif(($field['type'] ?? '') === 'textarea')
                <textarea id="{{ $formId }}-{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
            @else
                <input id="{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
            @endif
            @if($restore) @error($name)<span class="field-error">{{ $message }}</span>@enderror @endif
        </label>
    @endforeach
    <button class="button dark" type="submit">Opslaan</button>
</form>
