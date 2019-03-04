@extends('layouts.layout')

@section('title')
{{__('Groups')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
@include('shared.breadcrumbs', ['routes' => [
    __('Admin') => route('admin.index'),
    __('Groups') => null,
]])
<div class="container page-content" id="listGroups">
    <div class="row">
        <div class="col">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
                <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
            </div>

        </div>
        <div class="col-8" align="right">
            @can('create-groups')
            <a href="#" class="btn btn-secondary" data-toggle="modal" data-target="#createGroup"><i class="fas fa-plus"></i>
                {{__('Group')}}</a>
            @endcan
        </div>
    </div>
    <div class="container-fluid">
        <groups-listing ref="groupList" :filter="filter" :permission="{{ \Auth::user()->hasPermissionsFor('groups') }}" v-on:reload="reload"></groups-listing>
    </div>
</div>

    @can('create-groups')
        <div class="modal fade" id="createGroup" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5>{{__('Create Group')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!! Form::label('name', __('Name')) !!}
                            {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' =>
                            'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                            <small id="emailHelp" class="form-text text-muted">{{__('Group name must be distinct')}}</small>
                            <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('description', __('Description')) !!}
                            {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=>
                            'form-control', 'v-model' => 'formData.description', 'v-bind:class' => '{\'form-control\':true,
                            \'is-invalid\':errors.description}']) !!}
                            <div class="invalid-feedback" v-for="description in errors.description">@{{description}}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">{{__('Cancel')}}</button>
                        <button type="button" @click="onSubmit" class="btn btn-secondary ml-2">{{__('Save')}}</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('js')
    <script src="{{mix('js/admin/groups/index.js')}}"></script>

    @can('create-groups')
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
                    onClose() {
                        this.resetFormData();
                        this.resetErrors();
                    },
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
                    onSubmit() {
                        this.resetErrors();
                        ProcessMaker.apiClient.post('groups', this.formData)
                            .then(response => {
                                ProcessMaker.alert('{{__('The group was created.')}}', 'success');
                                //redirect show group
                                window.location = "/admin/groups/" + response.data.id + "/edit"
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
    @endcan
@endsection