@extends('layouts.layout')

@section('title')
    {{__('Configure Screen')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Screens') => route('screens.index'),
        $screen->title => null,
    ]])
    <div class="container" id="editGroup">
        <div class="row">
            <div class="col">
                <div class="card card-body">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {!! Form::label('title', __('Name')) !!}
                        {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.title',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.title}']) !!}
                        <small class="form-text text-muted" v-if="! errors.title">{{__('The screen name must be distinct.') }}</small>
                        <div class="invalid-feedback" v-if="errors.title">@{{errors.title[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('description', __('Description')) !!}
                        {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
                        'v-model' => 'formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.description}']) !!}
                        <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('category', __('Category')) !!}
                        {!! Form::text('category', null, ['id' => 'category','class'=> 'form-control', 'v-model' => 'formData.category',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.category}']) !!}
                        <small class="form-text text-muted" v-if="! errors.category">{{__('The screen name must be distinct.') }}</small>
                        <div class="invalid-feedback" v-if="errors.category">@{{errors.category[0]}}</div>
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
            el: '#editGroup',
            data() {
                return {
                    formData: @json($screen),
                    errors: {
                        'title': null,
                        'type': null,
                        'description': null,
                        'status': null
                    }
                }
            },
            methods: {
                resetErrors() {
                    this.errors = Object.assign({}, {
                        title: null,
                        type: null,
                        description: null,
                        status: null
                    });
                },
                onClose() {
                    window.location.href = '/designer/screens';
                },
                onUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('screens/' + this.formData.id, this.formData)
                        .then(response => {
                            ProcessMaker.alert('{{__('The screen was saved.')}}', 'success');
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