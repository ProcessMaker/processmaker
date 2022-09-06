@extends('layouts.layout')

@section('title')
    {{__('Edit Environment Variable')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Environment Variable') => route('environment-variables.index'),
        __('Edit') . " " . $environmentVariable->name => null,
    ]])
@endsection
@section('content')
    <div class="container" id="editEnvironmentVariable">
        <div class="row">
            <div class="col">
                <div class="card card-body">
                    <required></required>
                    <div class="form-group">
                        {!!Form::label('name', __('Name')  . '<small class="ml-1">*</small>', [], false)!!}
                        {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'formData.name',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}', 'required', 'aria-required' => 'true'])!!}
                        <small class="form-text text-muted" v-if="! errors.name">{{__('The environment variable name must be unique.') }}</small>
                        <div class="invalid-feedback" role="alert" v-for="name in errors.name">@{{name}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('description', __('Description')  . '<small class="ml-1">*</small>', [], false)!!}
                        {!!Form::textArea('description', null, ['class'=> 'form-control', 'v-model'=> 'formData.description',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}','rows'=>3, 'required', 'aria-required' => 'true'])!!}
                        <div class="invalid-feedback" role="alert" v-for="description in errors.description">@{{description}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('value', __('Value'))!!}
                        {!!Form::textArea('value', null, ['class'=> 'form-control', 'v-model'=> 'formData.value',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}','rows'=>3, 'required', 'aria-required' => 'true'])!!}
                        <small class="form-text text-muted">{{__('For security purposes, this field will always appear empty') }}</small>
                        <div class="invalid-feedback" role="alert" v-for="value in errors.value">@{{value}}</div>
                    </div>
                    <br>
                    <div class="text-right">
                        {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                        {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        new Vue({
            el: '#editEnvironmentVariable',
            data() {
                return {
                    formData: {
                        id: @json($environmentVariable->id),
                        name: @json($environmentVariable->name),
                        description: @json($environmentVariable->description),
                        value: null,
                    },
                    errors: {
                        'name': null,
                        'description': null,
                        'value': null
                    }
                }
            },
            methods: {
                resetErrors() {
                    this.errors = Object.assign({}, {
                        name: this.name,
                        description: this.description,
                        value: this.value
                    });
                },
                onClose() {
                    window.location.href = '/designer/environment-variables';
                },
                onUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('environment_variables/' + this.formData.id, this.formData)
                        .then(response => {
                            ProcessMaker.alert('{{__('The environment variable was saved.')}}', 'success');
                            this.onClose();
                        })
                        .catch(error => {
                            if (error.response.status && error.response.status === 422) {
                                this.errors = error.response.data.errors;
                            }
                        });
                }
            }
        });
    </script>
@endsection
