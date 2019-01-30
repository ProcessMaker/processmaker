@if isset($label)
    {!! Form::label($name, $label) !!}
@endif

@php
    if (!isset($model)) {
        $model = 'formData.' . $name;
    }
    $mustache = '{{ errors. ' . $name . '[0] }}';
@endphp
@if ($type == 'text')
    {!! Form::text($name, null, ['id' => $name,'class'=> 'form-control', 'v-model' => $model, 'v-bind:class' => '{\'form-control\':true,\'is-invalid\':errors.' . $name . '}']) !!}
@endif
<div class="invalid-feedback" v-if="errors.{{ $name }}">{{ $mustache }}</div>
