@extends('layouts.layout')

@section('title')
    {{__('Edit Users')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container" id="editUser">
        <h1>{{__('Edit User')}}</h1>
        <div class="row">
            <div class="col-8">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab"
                            aria-controls="nav-home" aria-selected="true">Information</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab"
                            aria-controls="nav-profile" aria-selected="false">Permissions</a>
                    </div>
                </nav>
                <div class="card card-body tab-content mt-3" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                        {!! Form::open() !!}
                        <div class="form-group">
                            {!!Form::label('username', __('Username'))!!}
                            {!!Form::text('username', null, ['class'=> 'form-control', 'v-model'=> 'formData.username',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.username}'])!!}
                            <div class="invalid-feedback" v-if="errors.username">@{{errors.username[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('firstname', __('First name'))!!}
                            {!!Form::text('firstname', null, ['class'=> 'form-control', 'v-model'=> 'formData.firstname',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.firstname}'])!!}
                            <div class="invalid-feedback" v-if="errors.firstname">@{{errors.firstname[0]}}
                            </div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('lastname', __('Last name'))!!}
                            {!!Form::text('lastname', null, ['class'=> 'form-control', 'v-model'=> 'formData.lastname',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.lastname}'])!!}
                            <div class="invalid-feedback" v-if="errors.lastname">@{{errors.lastname[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('status', 'Status');!!}
                            {!!Form::select('size', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], 'formData.status', ['class'=> 'form-control', 'v-model'=> 'formData.status',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}']);!!}
                            <div class="invalid-feedback" v-if="errors.email">@{{errors.status[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('email', __('Email'))!!}
                            {!!Form::email('email', null, ['class'=> 'form-control', 'v-model'=> 'formData.email',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.email}'])!!}
                            <div class="invalid-feedback" v-if="errors.email">@{{errors.email[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('password', __('Password'))!!}
                            {!!Form::password('password', ['class'=> 'form-control', 'v-model'=> 'formData.password',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}'])!!}
                            <div class="invalid-feedback" v-if="errors.password">@{{errors.password[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('confpassword', __('Confirm Password'))!!}
                            {!!Form::password('confpassword', ['class'=> 'form-control', 'v-model'=> 'formData.confpassword',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}'])!!}
                        </div>
                        <div class="form-group">
                            <label>{{__('Groups')}}</label>
                            <multiselect v-model="value" :options="dataGroups" :multiple="true" track-by="name"
                                        :custom-label="customLabel"
                                        :show-labels="false" label="name">

                                <template slot="tag" slot-scope="props">
                                <span class="multiselect__tag  d-flex align-items-center" style="width:max-content;">
                                    <img class="option__image mr-1" :src="props.option.img" alt="Check it">
                                    <span class="option__desc mr-1">@{{ props.option.name }}
                                        <span class="option__title">@{{ props.option.desc }}</span>
                                    </span>
                                    <i aria-hidden="true" tabindex="1" @click="props.remove(props.option)"
                                    class="multiselect__tag-icon"></i>
                                </span>
                                </template>

                                <template slot="option" slot-scope="props">
                                    <div class="option__desc d-flex align-items-center">
                                        <img class="option__image mr-1" :src="props.option.img" alt="options">
                                        <span class="option__title mr-1">@{{ props.option.name }}</span>
                                        <span class="option__small">@{{ props.option.desc }}</span>
                                    </div>
                                </template>
                            </multiselect>
                        </div>

                        <div class="text-right">
                            {!! Form::button('Cancel', ['class'=>'btn btn-outline-success', '@click' => 'onClose']) !!}
                            {!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onProfileUpdate']) !!}
                        </div>
                        {!! Form::close() !!}
                    </div>
                    <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th colspan="6"><label>Would you like to make this user an admin? <input type="checkbox" v-model="isAdmin"></label></th>
                                </tr>
                                <tr>
                                    <th class="text-right" colspan="6"><label>Select All Permissions <input type="checkbox" v-model="selectAll" @click="select" :disabled="isAdmin = isAdmin"></label></th>
                                </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td>Name</td>
                                <td>List</td>
                                <td>Create</td>
                                <td>Delete</td>
                                <td>Edit</td>
                                <td>View</td>
                              @php
                              $header = '';
                              $i = 0;
                              @endphp

                              @foreach ($all_permissions as $key => $value)

                              @php

                              if(strpos($value['guard_name'],'.') === false) continue;

                              list($guard,$action) = explode('.',$value['guard_name']);

                              if($header !== $guard) {

                              if($i > 0){
                              echo '</tr>';
                              }

                              echo '<tr>
                                <td>'.str_replace('_',' ',title_case($guard)).'</td>';

                                $header = $guard;

                                $i++;

                                }

                                @endphp

                                <td align="center>"><input type="checkbox" :value="{{$value['id']}}" v-model="selected" :disabled="isAdmin = isAdmin"></td>

                                @endforeach
                              </tr>
                            </tbody>
                        </table>
                        <hr class="mt-0">
                        <button class="btn btn-secondary float-right" @click="onPermissionUpdate">SUBMIT</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        new Vue({
            el: '#editUser',
            data() {
                return {
                    formData: @json($user),
                    options: @json($groups),
                    dataGroups: [],
                    value: [],
                    errors: {
                        username: null,
                        firstname: null,
                        lastname: null,
                        email: null,
                        password: null,
                        status: null
                    },
                    isAdmin: false,
                    permissions: @json($all_permissions),
                    userPermissionIds: @json($permission_ids),
                    selected: [],
                    selectAll: false,
                }
            },
            beforeMount() {
                this.isSuperUser()
            },
            created() {
                this.hasPermission()
            },
            mounted() {
                this.fillDataGroups(this.options);
            },
            methods: {
                resetErrors() {
                    this.errors = Object.assign({}, {
                        username: null,
                        firstname: null,
                        lastname: null,
                        email: null,
                        password: null,
                        status: null
                    });
                },
                customLabel(option) {
                    return ` ${option.img} ${option.name} ${option.desc}`
                },
                fillDataGroups(data) {
                    let that = this;
                    that.dataGroups = [];
                    let values = [];
                    data.forEach(value => {
                        let option = value;
                        option.img = '/img/avatar-placeholder.gif';
                        option.desc = ' ';
                        that.dataGroups.push(option);
                        //fill groups selected
                        if (that.formData && that.formData.hasOwnProperty('memberships')) {
                            that.formData.memberships.forEach(member => {
                                if (member.group_id === option.id) {
                                    values.push(option);
                                }
                            });
                        }
                    });
                    that.value = values;
                },
                onClose() {
                    window.location.href = '/admin/users';
                },
                validatePassword() {
                    if (!this.formData.password && !this.formData.confpassword) {
                        return true;
                    }
                    if (this.formData.password.trim() === '' && this.formData.confpassword.trim() === '') {
                        return true
                    }
                    if (this.formData.password !== this.formData.confpassword) {
                        this.errors.password = ['Passwords must match']
                        this.password = ''
                        this.submitted = false
                        return false
                    }
                    return true
                },
                onProfileUpdate() {
                    this.resetErrors();
                    if (!this.validatePassword()) return false;
                    let that = this;
                    ProcessMaker.apiClient.put('users/' + that.formData.id, that.formData)
                        .then(response => {
                            //Remove member that has previously registered and is not in the post data.
                            if (that.formData && that.formData.hasOwnProperty('memberships') && that.formData.memberships) {
                                that.formData.memberships.forEach(dataMember => {
                                    let deleteMember = true;
                                    that.value.forEach(group => {
                                        if (dataMember.group_id === group.id) {
                                            deleteMember = false;
                                            return false;
                                        }
                                    });
                                    if (deleteMember) {
                                        ProcessMaker.apiClient.delete('group_members/' + dataMember.id);
                                    }
                                });
                            }
                            const promises = [];
                            //Add member who were not previously registered.
                            that.value.forEach(group => {
                                let save = true;
                                if (that.formData && that.formData.hasOwnProperty('memberships') &&
                                    that.formData.memberships) {
                                    that.formData.memberships.forEach(dataMember => {
                                        if (dataMember.group_id === group.id) {
                                            save = false;
                                            return false;
                                        }
                                    });
                                }
                                if (save) {
                                    promises.push(new Promise(
                                        (resolve, reject) => {
                                            ProcessMaker.apiClient
                                                .post('group_members', {
                                                    'group_id': group.id,
                                                    'member_type': 'ProcessMaker\\Models\\User',
                                                    'member_id': that.formData.id
                                                })
                                                .then(() => {
                                                    that.formData.memberships.push({
                                                        group_id: group.id
                                                    });
                                                    resolve()
                                                })
                                                .catch(() => {
                                                    reject()
                                                })
                                        })
                                    )
                                }
                            });
                            Promise.all(promises)
                                .then(() => {
                                    ProcessMaker.alert('{{__('Update User Successfully')}}', 'success');
                                    that.onClose();
                                })
                                .catch(() => {
                                    ProcessMaker.alert('{{__('An error occurred while saving the Groups.')}}', 'danger')
                                })
                        })
                        .catch(error => {
                            //define how display errors
                            if (error.response.status && error.response.status === 422) {
                                // Validation error
                                that.errors = error.response.data.errors;
                            }
                        });
                },
                isSuperUser() {
                    if(this.formData.is_administrator === true) {
                        this.isAdmin = true
                    }
                },
                hasPermission() {
                    if(this.userPermissionIds){
                        this.selected = this.userPermissionIds
                    }
                },
                select() {
                    this.selected = [];
                    if (!this.selectAll) {
                        for (let permission in this.permissions) {
                            this.selected.push(this.permissions[permission].id);
                        }
                    }
                },
                onPermissionUpdate() {
                    if(this.isAdmin === true) {
                    ProcessMaker.apiClient.put("/users/" + this.formData.id, {
                        is_administrator: this.isAdmin,
                        email: this.formData.email,
                        username: this.formData.username
                    })
                    .then(response => {
                        ProcessMaker.alert('{{__('Admin successfully added ')}}', 'success');
                        location.reload();
                    })
                    }
                    else{
                        ProcessMaker.apiClient.put("/permissions", {
                            permission_ids: this.selected,
                            user_id: this.formData.id
                            })
                        .then(response => {
                            ProcessMaker.alert('{{__('Permission successfully added ')}}', 'success');
                            location.reload();
                        })
                    }
                 },
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
