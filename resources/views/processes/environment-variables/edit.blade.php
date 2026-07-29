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
                    <asset-link-fields
                        :asset-type.sync="formData.asset_type"
                        :value.sync="formData.value"
                        :errors="errors"
                        value-hint="{{ __('For security purposes, this field will always appear empty') }}"
                    ></asset-link-fields>
                    <do-not-update-switch v-model="formData.do_not_update"></do-not-update-switch>
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
        window.ProcessMaker.EnvironmentVariableEdit = {
            id: @json($environmentVariable->id),
            name: @json($environmentVariable->name),
            description: @json($environmentVariable->description),
            asset_type: @json($environmentVariable->asset_type),
            // Safe to expose when linked: value is the asset numeric ID, not a secret.
            value: @json($environmentVariable->asset_type ? $environmentVariable->value : null),
            do_not_update: @json((bool) $environmentVariable->do_not_update),
        };
    </script>
    <script src="{{mix('js/processes/environment-variables/edit.js')}}"></script>
@endsection
