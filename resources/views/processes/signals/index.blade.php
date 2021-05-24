@extends('layouts.layout')

@section('title')
    {{__('Signals')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Signals') => null,
    ]])
@endsection

@section('content')
    <div class="px-3" id="listSignals">
        <div id="search-bar" class="search mb-3" vcloak>
            <div class="d-flex flex-column flex-md-row">
                <div class="flex-grow-1">
                    <div id="search" class="mb-3 mb-md-0">
                        <div class="input-group w-100">
                            <input v-model="filter" class="form-control" placeholder="{{__('Search')}}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                @can('create-signals')
                <div class="d-flex ml-md-2 flex-column flex-md-row">
                    <a href="#" id="create_signal" class="btn btn-secondary" data-toggle="modal" data-target="#createSignal">
                        <i class="fas fa-plus"></i>
                        {{__('Signal')}}
                    </a>
                </div>
                @endcan
            </div>
        </div>
        <div class="container-fluid">
            <signals-listing ref="signalList" :filter="filter" :permission="{{ \Auth::user()->hasPermissionsFor('processes') }}" v-on:reload="reload"/>
        </div>
    </div>

    @can('create-processes')
    <div class="modal fade" id="createSignal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>{{__('New Signal')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                <div class="form-group">
                        {!! Form::label('name', __('Signal Name')) !!}
                        {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' =>
                        'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                        <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('id', __('Signal ID')) !!}
                        {!! Form::text('id', null, ['id' => 'id','class'=> 'form-control', 'v-model' =>
                        'formData.id', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.id}']) !!}
                        <div class="invalid-feedback" v-for="id in errors.id">@{{id}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::textarea('detail', null, ['id' => 'detail', 'rows' => 4, 'class'=> 'form-control', 'v-bind:placeholder' => '$t("Additional Details (optional)")',
                        'v-model' => 'formData.detail', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.detail}']) !!}
                        <div class="invalid-feedback" v-if="errors.detail">@{{errors.detail[0]}}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">
                        {{__('Cancel')}}
                    </button>
                    <button type="button" @click="onSubmit" class="btn btn-secondary" id="createsignal" :disabled="disabled">
                        {{__('Save')}}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endcan
@endsection

@section('js')
    <script src="{{mix('js/processes/signals/index.js')}}"></script>

    @can('create-processes')
    <script>
        new Vue({
            el: '#createSignal',
            data() {
                return {
                    formData: {},
                    errors: {
                        'name': null,
                        'id': null,
                    },
                    disabled: false
                }
            },
            mounted() {
                this.resetFormData();
                this.resetErrors();
            },
            methods: {
                onClose() {
                    this.resetFormData();
                    this.resetErrors();
                },
                resetFormData() {
                    this.formData = Object.assign({}, {
                        name: null,
                        id: null,
                    });
                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                        name: null,
                        id: null,
                    });
                },
                onSubmit() {
                    this.resetErrors();
                    //single click
                    if (this.disabled) {
                        return
                    }
                    this.disabled = true;
                    ProcessMaker.apiClient.post('signals', this.formData)
                        .then(response => {
                            ProcessMaker.alert("{{__('The signal was created.')}}", 'success');
                            //redirect list signal
                            window.location = '/designer/signals';
                        })
                        .catch(error => {
                            console.log('ERRROR!');
                            this.disabled = false;
                            //define how display errors
                            if (error.response.status && error.response.status === 422) {
                                // Validation error
                                this.errors = error.response.data.errors;
                                //ProcessMaker.alert(this.errors, 'warning');
                            }
                        });
                }
            }
        });
    </script>
    @endcan
@endsection
