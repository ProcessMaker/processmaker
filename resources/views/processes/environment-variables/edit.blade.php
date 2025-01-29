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
                        {{ html()->label(__('Name') . '<small class="ml-1">*</small>', 'name') }}
                        {{ html()->text('name')->class('form-control')->attribute('v-model', 'formData.name')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.name}')->required()->attribute('aria-required', 'true') }}
                        <small class="form-text text-muted" v-if="! errors.name">{{__('The environment variable name must be unique.') }}</small>
                        <div class="invalid-feedback" role="alert" v-for="name in errors.name">@{{name}}</div>
                    </div>
                    <div class="form-group">
                        {{ html()->label(__('Description') . '<small class="ml-1">*</small>', 'description') }}
                        {{ html()->textarea('description')->class('form-control')->attribute('v-model', 'formData.description')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.description}')->rows(3)->required()->attribute('aria-required', 'true') }}
                        <div class="invalid-feedback" role="alert" v-for="description in errors.description">@{{description}}</div>
                    </div>
                    <div class="form-group">
                        {{ html()->label(__('Value'), 'value') }}
                        {{ html()->textarea('value')->class('form-control')->attribute('v-model', 'formData.value')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.description}')->rows(3)->required()->attribute('aria-required', 'true') }}
                        <small class="form-text text-muted">{{__('For security purposes, this field will always appear empty') }}</small>
                        <div class="invalid-feedback" role="alert" v-for="value in errors.value">@{{value}}</div>
                    </div>
                    <br>
                    <div class="text-right">
                        {{ html()->button(__('Cancel'), 'button')->class('btn btn-outline-secondary')->attribute('@click', 'onClose') }}
                        {{ html()->button(__('Save'), 'button')->class('btn btn-secondary ml-2')->attribute('@click', 'onUpdate') }}
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
                            ProcessMaker.alert(this.$t('The environment variable was saved.'), 'success');
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
