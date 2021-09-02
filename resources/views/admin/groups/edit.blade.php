@extends('layouts.layout')

@section('title')
    {{__('Edit Group')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', ['routes' => [
    __('Admin') => route('admin.index'),
    __('Groups') => route('groups.index'),
    __('Edit') . " " . $group->name => null,
  ]])
@endsection
@section('content')
    <div class="container" id="editGroup">
        <div class="row">
            <div class="col-12">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home"
                        role="tab" aria-controls="nav-home" aria-selected="true">{{__('Group Details')}}</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-users" role="tab"
                        aria-controls="nav-profile" aria-selected="false">{{__('Users')}}</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-groups" role="tab"
                        aria-controls="nav-profile" aria-selected="false">{{__('Groups')}}</a>
                        <a class="nav-item nav-link" id="nav-permissions-tab" data-toggle="tab" href="#nav-permissions"
                        role="tab" aria-controls="nav-permissions"
                        aria-selected="false">{{__('Group Permissions')}}</a>
                    </div>
                </nav>

                <div class="tab-content" id="nav-tabContent">
                    <div class="card card-body border-top-0 tab-pane p-3 show active" id="nav-home" role="tabpanel"
                         aria-labelledby="nav-home-tab">
                        <div class="form-group">
                            {!! Form::label('name', __('Name') . '<small class="ml-1">*</small>', [], false) !!}
                            {!! Form::text('name', null, [
                            'id' => 'name',
                            'class'=> 'form-control',
                            'maxlength' => '255',
                            'v-model' => 'formData.name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                            <small class="form-text text-muted">{{__('Group name must be distinct')}}</small>
                            <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}</div>
                        </div>
                        <div class="form-group mt-3">
                            {!! Form::label('description', __('Description')) !!}
                            {!! Form::textarea('description', null, [
                            'id' => 'description',
                            'rows' => 4,
                            'class'=> 'form-control',
                            'v-model' => 'formData.description',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}']) !!}
                            <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                        </div>
                        <div class="form-group mt-3">
                            {!! Form::label('status', __('Status')) !!}
                            {!! Form::select('status', ['ACTIVE' => __('Active'), 'INACTIVE' => __('Inactive')], null, [
                            'id' => 'status',
                            'class' => 'form-control',
                            'v-model' => 'formData.status',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}']) !!}
                            <div class="invalid-feedback" v-if="errors.status">@{{errors.status[0]}}</div>
                        </div>
                        @isset($addons)
                            @foreach ($addons as $addon)
                                {!! __($addon['content']) !!}
                            @endforeach
                        @endisset
                        <br>
                        <div class="d-flex justify-content-end mt-3">
                            {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                            {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'onUpdate', 'id'=>'saveGroup']) !!}
                        </div>
                    </div>
                    <div class="card card-body border-top-0 tab-pane p-3" id="nav-users" role="tabpanel" aria-labelledby="nav-profile-tab">
                        <div id="search-bar" class="search mb-3" vcloak>
                            <div class="d-flex flex-column flex-md-row">
                                <div class="flex-grow-1">
                                    <div id="search" class="mb-3 mb-md-0">
                                        <div class="input-group w-100">
                                            <input id="users-filter" v-model="usersFilter" class="form-control" placeholder="{{__('Search')}}" aria-label="{{__('Search')}}">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex ml-md-2 flex-column flex-md-row">
                                    <button type="button" class="btn btn-secondary" @click="showAddUserModal" aria-label="{{ __('Add User') }}" aria-haspopup="dialog">
                                        <i class="fas fa-plus"></i>
                                        {{__('User')}}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <users-in-group ref="listing" :filter="usersFilter" :group-id="formData.id"></users-in-group>
                    </div>
                    <div class="card card-body border-top-0 tab-pane p-3" id="nav-groups" role="tabpanel" aria-labelledby="nav-profile-tab">
                        <div id="search-bar" class="search mb-3" vcloak>
                            <div class="d-flex flex-column flex-md-row">
                                <div class="flex-grow-1">
                                    <div id="search" class="mb-3 mb-md-0">
                                        <div class="input-group w-100">
                                            <input id="groups-filter" v-model="groupsFilter" class="form-control" placeholder="{{__('Search')}}" aria-label="{{__('Search')}}">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex ml-md-2 flex-column flex-md-row">
                                    <button type="button" class="btn btn-secondary" @click="showAddGroupModal" aria-label="{{ __('Add Group') }}" aria-haspopup="dialog">
                                        <i class="fas fa-plus"></i>
                                        {{__('Group')}}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <groups-in-group ref="groupListing" :filter="groupsFilter" :group-id="formData.id"></groups-in-group>
                    </div>
                    <div class="card card-body border-top-0 tab-pane p-3" id="nav-permissions" role="tabpanel" aria-labelledby="nav-permissions">
                        <div class="accordion" id="accordionPermissions">
                            <div class="mb-3 custom-control custom-switch">
                                <input id="selectAll" type="checkbox" v-model="selectAll" class="custom-control-input" @click="select">
                                <label class="custom-control-label" for="selectAll">{{ __('Assign all permissions to this group') }}</label>
                            </div>
                            @include('admin.shared.permissions')
                            <div class="d-flex justify-content-end mt-3">
                                {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose'])!!}
                                {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'permissionUpdate','id'=>'savePermissions'])!!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <pm-modal ref="addUser" id="addUser" title="{{__('Add Users')}}" @hidden="onCloseAddUser" @ok.prevent="onSave" style="display: none;">
                <div class="form-user">
                    {!!Form::label('users', __('Users') . '<small class="ml-1">*</small>', [], false)!!}
                    <multiselect id="users"
                                 v-model="selectedUsers"
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
        
                        <template slot="noResult" >
                            {{ __('No elements found. Consider changing the search query.') }}
                        </template>
        
                        <template slot="noOptions" >
                            {{ __('No Data Available') }}
                        </template>
        
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
                                <span class="option__title mr-1">@{{ props.option.fullname }} (@{{ props.option.username }})</span>
                            </div>
                        </template>
                    </multiselect>
                </div>
            </pm-modal>
            
            <pm-modal ref="addGroup" id="addGroup" title="{{__('Add Groups')}}" @hidden="onCloseAddGroup" @ok.prevent="onSaveGroups" style="display: none;">
                <div class="form-user">
                    {!!Form::label('groups', __('Groups') . '<small class="ml-1">*</small>', [], false)!!}
                    <multiselect id="groups"
                                 v-model="selectedGroups"
                                 placeholder="{{__('Select group or type here to search groups')}}"
                                 :options="availableGroups"
                                 :multiple="true"
                                 track-by="name"
                                 :show-labels="false"
                                 :searchable="true"
                                 :internal-search="false"
                                 @search-change="loadGroups"
                                 label="name">

                        <template slot="noResult" >
                            {{ __('No elements found. Consider changing the search query.') }}
                        </template>

                        <template slot="noOptions" >
                            {{ __('No Data Available') }}
                        </template>

                        <template slot="tag" slot-scope="props">
                            <span class="multiselect__tag  d-flex align-items-center"
                                  style="width:max-content;">
                                <span class="option__desc mr-1">
                                    <span class="option__title">@{{ props.option.name }}</span>
                                </span>
                                <i aria-hidden="true" tabindex="1" @click="props.remove(props.option)"
                                   class="multiselect__tag-icon"></i>
                            </span>
                        </template>

                        <template slot="option" slot-scope="props">
                            <div class="option__desc d-flex align-items-center">
                                <span class="option__title mr-1">@{{ props.option.name }}</span>
                            </div>
                        </template>
                    </multiselect>
                </div>
            </pm-modal>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/groups/edit.js')}}"></script>
    <script>
      new Vue({
        el: '#editGroup',
        mixins:addons,
        data() {
          return {
            formData: @json($group),
            usersFilter: '',
            groupsFilter: '',
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
            availableUsers: [],
            selectedGroups: [],
            availableGroups: [],
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
          onCloseAddGroup() {
            this.selectedGroups = [];
          },
          onSave() {
            this.selectedUsers.forEach(user => {
              ProcessMaker.apiClient
                .post('group_members', {
                  'group_id': this.formData.id,
                  'member_type': 'ProcessMaker\\Models\\User',
                  'member_id': user.id
                })
                .then(response => {
                  this.$refs['listing'].fetch();
                  this.$refs.addUser.hide();
                  this.selectedUsers = [];
                });
            })
          },
          onSaveGroups() {
            this.selectedGroups.forEach(group => {
              ProcessMaker.apiClient
                .post('group_members', {
                  'group_id': this.formData.id,
                  'member_type': 'ProcessMaker\\Models\\Group',
                  'member_id': group.id
                })
                .then(response => {
                  this.$refs['groupListing'].fetch();
                  this.$refs.addGroup.hide();
                  this.selectedGroups = [];
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
                ProcessMaker.alert(this.$t('Update Group Successfully'), 'success');
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
          loadGroups(filter) {
            filter = typeof filter === 'string' ? '?filter=' + filter + '&' : '?';
            ProcessMaker.apiClient
                .get(
                    "group_members_available" + filter +
                    "group_id=" + this.formData.id
                )
                .then(response => {
                    this.availableGroups = response.data.data
                });
          },
          permissionUpdate() {
            ProcessMaker.apiClient.put("/permissions", {
              permission_names: this.selectedPermissions,
              group_id: this.formData.id
            })
              .then(response => {
                ProcessMaker.alert(this.$t('Group Permissions Updated Successfully'), 'success');
                this.onClose();
              })
          },
          showAddUserModal() {
            this.loadUsers();
            this.$refs.addUser.show();
          },
          showAddGroupModal() {
            this.loadGroups();
            this.$refs.addGroup.show();
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
