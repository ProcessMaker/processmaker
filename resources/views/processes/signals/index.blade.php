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
    <div id="listSignals">
        <div class="px-3">
            <div id="search-bar" class="search mb-3" vcloak>
                <div class="d-flex flex-column flex-md-row">
                    <div class="flex-grow-1">
                        <div id="search" class="mb-3 mb-md-0">
                            <div class="input-group w-100">
                                <input id="search-box" v-model="filter" class="form-control" placeholder="{{__('Search')}}"  aria-label="{{__('Search')}}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @can('create-signals')
                    <div class="d-flex ml-md-2 flex-column flex-md-row">
                        <button class="btn btn-secondary" aria-label="{{ __('Create Signal') }}" @click="$refs.createSignal.show()">
                            <i class="fas fa-plus"></i>
                            {{__('Signal')}}
                        </button>
                    </div>
                    @endcan
                </div>
            </div>
            <div class="container-fluid">
                <signals-listing ref="signalList" :filter="filter" :permission="{{ \Auth::user()->hasPermissionsFor('processes') }}" v-on:reload="reload"/>
            </div>
        </div>
        
        @can('create-processes')
            <pm-modal ref="createSignal" id="createSignal" title="{{__('New Signal')}}" @hidden="onClose" @ok.prevent="onSubmit" :ok-disabled="disabled" style="display: none;">
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
                    'aria-label' => __('Additional Details (optional)'),
                    'v-model' => 'formData.detail', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.detail}']) !!}
                    <div class="invalid-feedback" v-if="errors.detail">@{{errors.detail[0]}}</div>
                </div>
            </pm-modal>
        @endcan
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/signals/index.js')}}"></script>
    <script>
        new Vue({
            el: '#listSignals',
            data() {
                return {
                    filter: "",
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
                reload () {
                    this.$refs.signalList.dataManager([
                        {
                            field: "name",
                            direction: "desc"
                        }
                    ]);
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
@endsection
