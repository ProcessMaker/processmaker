@extends('layouts.layout')

@section('title')
    {{__('Process Categories')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container page-content" id="process-variables-listing">
        <h1>{{__('Environment Variables')}}</h1>
        <div class="row">
            <div class="col">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                        <i class="fas fa-search"></i>
                        </span>
                    </div>
                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                </div>
            </div>
            <div class="col-8" align="right">
                <button type="button" class="btn btn-action text-light" data-toggle="modal"
                        data-target="#createEnvironmentVariable">
                    <i class="fas fa-plus"></i> {{__('Environment Variable')}}
                </button>
            </div>
        </div>
        <variables-listing ref="listVariable" :filter="filter" @delete="deleteVariable"></variables-listing>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="createEnvironmentVariable">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Create New Process Variable')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!!Form::label('name', __('Variable Name'))!!}
                        {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                        <small class="form-text text-muted">{{ __('Variable Name must be distinct') }}</small>
                        <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('description', __('Description'))!!}
                        {!!Form::textArea('description', null, ['class'=> 'form-control', 'v-model'=> 'description',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}'])!!}
                        <div class="invalid-feedback" v-for="description in errors.description">@{{description}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('value', __('Value'))!!}
                        {!!Form::text('value', null, ['class'=> 'form-control', 'v-model'=> 'value',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.value}'])!!}
                        <div class="invalid-feedback" v-for="value in errors.value">@{{value}}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                            data-dismiss="modal">{{__('Close')}}</button>
                    <button type="button" class="btn btn-secondary" @click="onSubmit"
                            id="disabledForNow">{{__('Save')}}</button>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/environment-variables/index.js')}}"></script>
    <script>
        new Vue({
            el: '#createEnvironmentVariable',
            data: {
                errors: {},
                name: '',
                description: '',
                value: ''
            },
            methods: {
                onSubmit() {
                    this.errors = {};
                    let that = this;
                    ProcessMaker.apiClient.post('environment_variables', {
                        name: this.name,
                        description: this.description,
                        value: this.value
                    })
                        .then(response => {
                            ProcessMaker.alert('{{__('Variable successfully added ')}}', 'success');
                            window.location = '/processes/environment-variables';
                        })
                        .catch(error => {
                            if (error.response.status === 422) {
                                that.errors = error.response.data.errors
                            }
                        });
                }
            }
        })
    </script>
@endsection
