@extends('layouts.layout')

@section('title')
    {{__('Edit Groups')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container" id="editGroup">
        <h1>{{__('Edit Group')}}</h1>
        <div class="row">
            <div class="col-8">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab"
                           aria-controls="nav-home" aria-selected="true">Group Details</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-users" role="tab"
                           aria-controls="nav-profile" aria-selected="false">Group Members</a>
                    </div>
                </nav>


                <div class="tab-content mt-3" id="nav-tabContent">
                    <div class="card card-body tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                        {!! Form::open() !!}
                        <div class="form-group">
                            {!! Form::label('name', 'Name') !!}
                            {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' => 'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                            <small class="form-text text-muted">Group name must be distinct</small>
                            <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('description', 'Description') !!}
                            {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control', 'v-model' => 'formData.description', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}']) !!}
                            <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('status', 'Status') !!}
                            {!! Form::select('status', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], null, ['id' => 'status', 'class' => 'form-control', 'v-model' => 'formData.status', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}']) !!}
                            <div class="invalid-feedback" v-if="errors.status">@{{errors.status[0]}}</div>
                        </div>
                        <br>
                        <div class="text-right">
                            {!! Form::button('Cancel', ['class'=>'btn btn-outline-success', '@click' => 'onClose']) !!}
                            {!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onUpdate']) !!}
                        </div>
                        {!! Form::close() !!}
                    </div>
                    <div class="tab-pane fade" id="nav-users" role="tabpanel" aria-labelledby="nav-profile-tab">
                        <div class="row">
                            <div class="col">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                    </div>
                                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                                </div>

                            </div>
                            <div class="col-8" align="right">
                                <button type="button" class="btn btn-action text-light" data-toggle="modal" data-target="#addUser">
                                    <i class="fas fa-plus"></i>
                                    {{__('User')}}</button>
                            </div>
                        </div>
                        <users-in-group ref="listing" :filter="filter" :group-id="formData.id"></users-in-group>
                    </div>
                </div>
            </div>

            <div class="modal" tabindex="-1" role="dialog" id="addUser">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{__('Add Users')}}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-user">
                                {!!Form::label('users', __('Users'))!!}
                                <multiselect v-model="selectedUsers" :options="availableUsers" :multiple="true"
                                             track-by="fullname"
                                             :custom-label="customLabel" :show-labels="false" label="fullname">

                                    <template slot="tag" slot-scope="props">
                                        <span class="multiselect__tag  d-flex align-items-center" style="width:max-content;">
                                            <span class="option__desc mr-1">
                                                <span class="option__title">@{{ props.option.fullname }}</span>
                                            </span>
                                            <i aria-hidden="true" tabindex="1" @click="props.remove(props.option)"
                                               class="multiselect__tag-icon"></i>
                                        </span>
                                    </template>

                                    <template slot="option" slot-scope="props">
                                        <div class="option__desc d-flex align-items-center">
                                            <span class="option__title mr-1">@{{ props.option.fullname }}</span>
                                        </div>
                                    </template>
                                </multiselect>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                    data-dismiss="modal">{{__('Close')}}</button>
                            <button type="button" class="btn btn-secondary" @click="onSave"
                                    id="disabledForNow">{{__('Save')}}</button>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-4">
                <div class="card card-body">
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore
                    et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
                    aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                    cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
                    culpa qui officia deserunt mollit anim id est laborum.
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/groups/edit.js')}}"></script>
    <script>
        new Vue({
            el: '#editGroup',
            data() {
                return {
                    showAddUserModal:false,
                    formData: @json($group),
                    filter: '',
                    errors: {
                        'name': null,
                        'description': null,
                        'status': null
                    },
                    selectedUsers: [],
                    availableUsers: @json($users)
                }
            },
            methods: {
                customLabel(options) {
                    return `${options.fullname}`
                },
                onSave() {
                    let that = this;
                    this.selectedUsers.forEach(function (user) {
                        ProcessMaker.apiClient
                            .post('group_members', {
                                'group_id': that.formData.id,
                                'member_type': 'ProcessMaker\\Models\\User',
                                'member_id': user.id
                            });
                    })
                    this.$refs['listing'].fetch();
                    $('#addUser').modal('hide');
                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                        name: null,
                        description: null,
                        status: null
                    });
                },
                onClose() {
                    window.location.href = '/admin/groups';
                },
                onUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('groups/' + this.formData.id, this.formData)
                        .then(response => {
                            ProcessMaker.alert('{{__('Update Group Successfully')}}', 'success');
                            this.onClose();
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
@endsection

@section('css')
    <style>
        .inline-input {
            margin-right: 6px;
        }
        .inline-button {
            background-color: rgb(109, 124, 136);
            font-weight: 100;
        }
        .input-and-select {
            width: 212px;
        }
        .multiselect__element span img {
            border-radius: 50%;
            height: 20px;
        }
        .multiselect__tags-wrap {
            display: flex !important;
        }
        .multiselect__tags-wrap img {
            height: 15px;
            border-radius: 50%;
        }
        .multiselect__tag-icon:after {
            color: white !important;
        }
        .multiselect__option--highlight {
            background: #00bf9c !important;
        }
        .multiselect__option--selected.multiselect__option--highlight {
            background: #00bf9c !important;
        }
        .multiselect__tags {
            border: 1px solid #b6bfc6 !important;
            border-radius: 0.125em !important;
            height: calc(1.875rem + 2px) !important;
        }
        .multiselect__tag {
            background: #788793 !important;
        }
        .multiselect__tag-icon:after {
            color: white !important;
        }
    </style>
@endsection