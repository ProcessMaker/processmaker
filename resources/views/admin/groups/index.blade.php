@extends('layouts.layout')

@section('title')
    {{__('Groups')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" id="listGroups" v-cloak>
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Groups')}}</h1>
                        <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <button type="button" href="#" class="btn btn-action text-white" data-toggle="modal"
                                data-target="#createGroup">
                            <i class="fas fa-plus"></i> {{__('Group')}}
                        </button>
                    </div>
                </div>
                <groups-listing ref="groupList" :filter="filter" v-on:reload="reload"></groups-listing>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createGroup" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1>{{__('Create New Group')}}</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!! Form::label('name', 'Name') !!}
                        {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' => 'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                        <small id="emailHelp" class="form-text text-muted">Group name must be distinct</small>
                        <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('description', 'Description') !!}
                        {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control', 'v-model' => 'formData.description', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}']) !!}
                        <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
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
    <script>
        new Vue({
            el: '#createGroup',
            data() {
                return {
                    formData: {},
                    errors: {
                        'name': null,
                        'description': null,
                        'status': null
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
                        name: null,
                        description: null,
                        status: 'ACTIVE'
                    });
                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                        name: null,
                        description: null,
                        status: null
                    });
                },
                onClose() {
                    $('#createGroup').modal('hide');
                },
                onSubmit() {
                    this.resetErrors();
                    ProcessMaker.apiClient.post('groups', this.formData)
                        .then(response => {
                            ProcessMaker.alert('Create Group Successfully', 'success');
                            this.onClose();
                            //redirect show group
                            window.location = "/admin/groups/" + response.data.uuid
                        })
                        .catch(error => {
                            //define how display errors
                            if (error.response.status && error.response.status === 422) {
                                // Validation error
                                this.errors = error.response.data.errors;
                            }
                        });
                }
            }
        });
    </script>
    <script src="{{mix('js/admin/groups/index.js')}}"></script>
@endsection
