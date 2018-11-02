@extends('layouts.layout')

@section('title')
{{__('Groups')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div class="container page-content" id="listGroups">
    <h1>{{__('Groups')}}</h1>
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
            <a href="#" class="btn btn-action" data-toggle="modal" data-target="#createGroup"><i class="fas fa-plus"></i>
                {{__('Group')}}</a>
        </div>
    </div>
    <div class="container-fluid">
        <groups-listing ref="groupList" :filter="filter" v-on:reload="reload"></groups-listing>
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
                    {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' =>
                    'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                    <small id="emailHelp" class="form-text text-muted">Group name must be distinct</small>
                    <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                </div>
                <div class="form-group">
                    {!! Form::label('description', 'Description') !!}
                    {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=>
                    'form-control', 'v-model' => 'formData.description', 'v-bind:class' => '{\'form-control\':true,
                    \'is-invalid\':errors.description}']) !!}
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
            onSubmit() {
                this.resetErrors();
                ProcessMaker.apiClient.post('groups', this.formData)
                    .then(response => {
                        ProcessMaker.alert('{{__('Create Group Successfully')}}', 'success');
                        //redirect show group
                        window.location = "/admin/groups/" + response.data.id
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