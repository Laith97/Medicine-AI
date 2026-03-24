@props([
    'providerKey',
    'prefix',
    'requirements',
    'config'
])

@foreach($requirements as $fieldKey => $fieldLabel)
    @php
        // Determine input type based on field name
        $inputType = 'text';
        if (str_contains(strtolower($fieldKey), 'password')) {
            $inputType = 'password';
        } elseif (str_contains(strtolower($fieldKey), 'email')) {
            $inputType = 'email';
        } elseif (str_contains(strtolower($fieldKey), 'token') || str_contains(strtolower($fieldKey), 'key') || str_contains(strtolower($fieldKey), 'secret')) {
            $inputType = 'password';
        }

        $id = $prefix . '_' . $fieldKey . '_' . $providerKey;
        $name = 'provider_config[' . $fieldKey . ']';
        $value = old($name, $config ? ($config->provider_config[$fieldKey] ?? '') : '');
    @endphp
    <div class="col-md-6">
        <div class="mb-3">
            <label for="{{ $id }}" class="form-label">{{ $fieldLabel }}</label>
            <input type="{{ $inputType }}"
                   class="form-control"
                   name="{{ $name }}"
                   id="{{ $id }}"
                   value="{{ $value }}">
        </div>
    </div>
@endforeach
