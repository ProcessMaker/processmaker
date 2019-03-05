@extends('layouts.layout')

@section('title')
    {{__('Configure Script')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Processes') => route('processes.index'),
        __('Scripts') => route('scripts.index'),
        __('Configure') . " " . $script->title => null,
    ]])
    <div class="container" id="editScript">
        <div class="row">
            <div class="col">
                <div class="card card-body">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {!! Form::label('title', __('Name')) !!}
                        {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.title',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.title}']) !!}
                        <small class="form-text text-muted" v-if="! errors.title">{{ __('The script name must be distinct.') }}</small>
                        <div class="invalid-feedback" v-if="errors.title">@{{errors.title[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('language', __('Language')) !!}
                        {!! Form::select('language', ['php' => 'PHP', 'lua' => 'LUA'], 'null', ['id' => 'language','class'=> 'form-control', 'v-model' => 'formData.language',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.language}']) !!}
                        <div class="invalid-feedback" v-for="language in errors.language">@{{language}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('description', __('Description')) !!}
                        {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
                        'v-model' => 'formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.description}']) !!}
                        <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                    </div>
                    <br>
                    <div class="text-right">
                        {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                        {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
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
            el: '#editScript',
            data() {
                return {
                    formData: @json($script),
                    errors: {
                        'title': null,
                        'language': null,
                        'description': null,
                        'status': null
                    }
                }
            },
            methods: {
                resetErrors() {
                    this.errors = Object.assign({}, {
                        title: null,
                        language: null,
                        description: null,
                        status: null
                    });
                },
                onClose() {
                    window.location.href = '/processes/scripts';
                },
                onUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('scripts/' + this.formData.id, {
                        title: this.formData.title,
                        language: this.formData.language,
                        description: this.formData.description,
                    })
                        .then(response => {
                            ProcessMaker.alert('{{__('The script was saved.')}}', 'success');
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
