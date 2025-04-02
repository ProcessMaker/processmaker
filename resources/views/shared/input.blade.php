@if (isset($label))
    {{ html()->label($label, $name) }}
@endif

@php
    if (!isset($model)) {
        $model = 'formData.' . $name;
    }
    $mustache = '{{ errors. ' . $name . '[0] }}';
@endphp
@if ($type == 'text')
    {{ html()->text($name)->id($name)->class('form-control')->attribute('v-model', $model)->attribute('v-bind:class', '{\'form-control\':true,\'is-invalid\':errors.' . $name . '}') }}
@endif
<div class="invalid-feedback" role="alert" v-if="errors.{{ $name }}">{{ $mustache }}</div>
