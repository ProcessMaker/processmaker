@extends('layouts.layout')

@section('title')
    {{__('Edit Screens')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container" id="editGroup">
        <h1>{{__('Edit Screen')}}</h1>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {!! Form::label('title', 'Name') !!}
                        {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.title',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.title}']) !!}
                        <small class="form-text text-muted">Form title must be distinct</small>
                        <div class="invalid-feedback" v-if="errors.title">@{{errors.title[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('type', 'Type') !!}
                        {!! Form::select('type', ['DISPLAY' => 'Display', 'FORM' => 'Form'], 'null', ['id' => 'type','class'=> 'form-control', 'v-model' => 'formData.type',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.type}']) !!}
                        <div class="invalid-feedback" v-for="type in errors.type">@{{type}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('description', 'Description') !!}
                        {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
                        'v-model' => 'formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.description}']) !!}
                        <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
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
                    window.location.href = '/processes/screens';
                },
                onUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('screens/' + this.formData.id, this.formData)
                        .then(response => {
                            ProcessMaker.alert('Updated Screen Successfully', 'success');
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