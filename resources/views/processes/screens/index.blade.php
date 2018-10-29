@extends('layouts.layout')

@section('title')
    {{__('Screens')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container page-content" id="screenIndex">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Screens')}}</h1>
                        <input id="processes-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <button type="button" href="#" class="btn btn-action text-white" data-toggle="modal"
                                data-target="#createScreen">
                            <i class="fas fa-plus"></i> {{__('Screen')}}
                        </button>
                    </div>
                </div>
                <screen-listing ref="screenListing" :filter="filter" v-on:reload="reload"></screen-listing>
            </div>

        </div>
    </div>

    <div class="modal fade" id="createScreen" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1>{{__('Create New Screen')}}</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!! Form::label('title', 'Name') !!}
                        {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.title',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.title}']) !!}
                        <small id="emailHelp" class="form-text text-muted">Screen title must be distinct</small>
                        <div class="invalid-feedback" v-for="title in errors.title">@{{title}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('description', 'Description') !!}
                        {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
                        'v-model' => 'formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.description}']) !!}
                        <div class="invalid-feedback" v-for="description in errors.description">@{{description}}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-success" data-dismiss="modal">{{__('Close')}}</button>
                    <button type="button" @click="onSubmit" class="btn btn-success ml-2">{{__('Save')}}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/screens/index.js')}}"></script>
    <script>
        new Vue({
            el: '#createScreen',
            data() {
                return {
                    formData: {},
                    errors: {
                        'title': null,
                        'description': null,
                    }
                }
            },
            mounted() {
                this.resetFormData();
                this.resetErrors();
            },
            methods: {
                resetFormData() {
                    this.formData = Object.assign({}, {
                        title: null,
                        description: null,
                    });
                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                        title: null,
                        description: null,
                    });
                },
                onSubmit() {
                    this.resetErrors();
                    ProcessMaker.apiClient.post('screens', this.formData)
                        .then(response => {
                            ProcessMaker.alert('Created Screen Successfully', 'success');
                            window.location = '/processes/screens';
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
