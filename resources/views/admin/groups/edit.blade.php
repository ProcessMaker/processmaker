@extends('layouts.layout')

@section('title')
    {{__('Edit Group')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Groups') => route('groups.index'),
        __('Edit') . " " . $group->name => null,
    ]])
    <div class="container" id="editGroup">
        <div class="row">
            <div class="col-12">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home"
                           role="tab" aria-controls="nav-home" aria-selected="true">{{__('Group Details')}}</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-users" role="tab"
                           aria-controls="nav-profile" aria-selected="false">{{__('Group Members')}}</a>
                        <a class="nav-item nav-link" id="nav-permissions-tab" data-toggle="tab" href="#nav-permissions"
                           role="tab" aria-controls="nav-permissions"
                           aria-selected="false">{{__('Group Permissions')}}</a>
                    </div>
                </nav>

                <div class="tab-content mt-3" id="nav-tabContent">
                    <div class="card card-body tab-pane fade show active" id="nav-home" role="tabpanel"
                         aria-labelledby="nav-home-tab">
                        {!! Form::open() !!}
                        <div class="form-group">
                            {!! Form::label('name', __('Name')) !!}
                            {!! Form::text('name', null, [
                            'id' => 'name',
                            'class'=> 'form-control',
                            'maxlength' => '255',
                            'v-model' => 'formData.name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                            <small class="form-text text-muted">{{__('Group name must be distinct')}}</small>
                            <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('description', __('Description')) !!}
                            {!! Form::textarea('description', null, [
                            'id' => 'description',
                            'rows' => 4,
                            'class'=> 'form-control',
                            'v-model' => 'formData.description',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}']) !!}
                            <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('status', __('Status')) !!}
                            {!! Form::select('status', ['ACTIVE' => __('Active'), 'INACTIVE' => __('Inactive')], null, [
                            'id' => 'status',
                            'class' => 'form-control',
                            'v-model' => 'formData.status',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}']) !!}
                            <div class="invalid-feedback" v-if="errors.status">@{{errors.status[0]}}</div>
                        </div>
                        <br>
                        <div class="text-right">
                            {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                            {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
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
                                <button type="button" class="btn btn-action text-light" data-toggle="modal"
                                        data-target="#addUser" @click="loadUsers">
                                    <i class="fas fa-plus"></i>
                                    {{__('User')}}
                                </button>
                            </div>
                        </div>
                        <users-in-group ref="listing" :filter="filter" :group-id="formData.id"></users-in-group>
                    </div>
                    <div class="tab-pane fade" id="nav-permissions" role="tabpanel" aria-labelledby="nav-permissions">
                        <div class="card">
                            <div class="card-body">
                                <label class="mb-3">
                                    <input type="checkbox" v-model="selectAll" @click="select"
                                           :disabled="formData.is_administrator">
                                    {{__('Assign all permissions to this group')}}
                                </label>
                                @include('admin.shared.permissions')
                                <div class="text-right mt-2">
                                    {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose'])!!}
                                    {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'permissionUpdate'])!!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal" tabindex="-1" role="dialog" id="addUser">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{__('Add Users')}}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                    @click="onCloseAddUser">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-user">
                                {!!Form::label('users', __('Users'))!!}
                                <multiselect v-model="selectedUsers"
                                             placeholder="{{__('Select user or type here to search users')}}"
                                             :options="availableUsers"
                                             :multiple="true"
                                             track-by="fullname"
                                             :custom-label="customLabel"
                                             :show-labels="false"
                                             :searchable="true"
                                             :internal-search="false"
                                             @search-change="loadUsers"
                                             label="fullname">

                                    <template slot="tag" slot-scope="props">
                                        <span class="multiselect__tag  d-flex align-items-center"
                                              style="width:max-content;">
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
                                    data-dismiss="modal" @click="onCloseAddUser">{{__('Cancel')}}</button>
                            <button type="button" class="btn btn-secondary ml-2" @click="onSave">{{__('Save')}}</button>
                        </div>
                    </div>
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
            showAddUserModal: false,
            formData: @json($group),
            filter: '',
            errors: {
              'name': null,
              'description': null,
              'status': null
            },
            groupPermissionNames: @json($permissionNames),
            permissions: @json($all_permissions),
            selectAll: false,
            selectedPermissions: [],
            selectedUsers: [],
            availableUsers: []
          }
        },
        created() {
          this.hasPermission()
        },
        watch: {
          selectedPermissions: function () {
            if (this.selectedPermissions.length !== this.permissions.length) {
              this.selectAll = false;
            }
          }
        },
        methods: {
          checkCreate(sibling, $event) {
            let self = $event.target.value;
            if (this.selectedPermissions.includes(self)) {
              this.selectedPermissions.push(sibling);
            }
          },
          checkEdit(sibling, $event) {
            let self = $event.target.value;
            if (!this.selectedPermissions.includes(self)) {
              this.selectedPermissions = this.selectedPermissions.filter(function (el) {
                return el !== sibling;
              });
            }
          },
          select() {
            this.selectedPermissions = [];
            if (!this.selectAll) {
              for (let permission in this.permissions) {
                this.selectedPermissions.push(this.permissions[permission].name);
              }
            }
          },
          customLabel(options) {
            return `${options.fullname}`
          },
          hasPermission() {
            if (this.groupPermissionNames) {
              this.selectedPermissions = this.groupPermissionNames;
            }
          },
          onCloseAddUser() {
            this.selectedUsers = [];
          },
          onSave() {
            let that = this;
            this.selectedUsers.forEach(function (user) {
              ProcessMaker.apiClient
                .post('group_members', {
                  'group_id': that.formData.id,
                  'member_type': 'ProcessMaker\\Models\\User',
                  'member_id': user.id
                })
                .then(response => {
                  that.$refs['listing'].fetch();
                  $('#addUser').modal('hide');
                  that.selectedUsers = [];
                });
            })
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
          },
          loadUsers(filter) {
            filter = typeof filter === 'string' ? '?filter=' + filter + '&' : '?';
            ProcessMaker.apiClient
              .get(
                "user_members_available" + filter +
                "group_id=" + this.formData.id
              )
              .then(response => {
                this.availableUsers = response.data.data
              });
          },
          permissionUpdate() {
            ProcessMaker.apiClient.put("/permissions", {
              permission_names: this.selectedPermissions,
              group_id: this.formData.id
            })
              .then(response => {
                ProcessMaker.alert('{{__('Group Permissions Updated Successfully ')}}', 'success');
                this.onClose();
              })
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