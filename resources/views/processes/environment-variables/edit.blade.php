@extends('layouts.layout')

@section('title')
    {{__('Edit Environment Variables')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container" id="editEnvironmentVariable">
        <h1>{{__('Edit Environment Variable')}}</h1>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {!!Form::label('name', __('Variable Name'))!!}
                        {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'formData.name',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                        <small class="form-text text-muted">{{ __('Variable Name must be distinct') }}</small>
                        <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('description', __('Description'))!!}
                        {!!Form::textArea('description', null, ['class'=> 'form-control', 'v-model'=> 'formData.description',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}','rows'=>3])!!}
                        <div class="invalid-feedback" v-for="description in errors.description">@{{description}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('value', __('Value'))!!}
                        {!!Form::text('value', null, ['class'=> 'form-control', 'v-model'=> 'formData.value',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.value}'])!!}
                        <div class="invalid-feedback" v-for="value in errors.value">@{{value}}</div>
                    </div>
                    <br>
                    <div class="text-right">
                        {!! Form::button('Cancel', ['class'=>'btn btn-outline-success', '@click' => 'onClose']) !!}
                        {!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onUpdate']) !!}
                    </div>
                    {!! Form::close() !!}
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
                    window.location.href = '/processes/environment-variables';
                },
                onUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('environment_variables/' + this.formData.id, this.formData)
                        .then(response => {
                            ProcessMaker.alert('Update Environment Variable Successfully', 'success');
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
