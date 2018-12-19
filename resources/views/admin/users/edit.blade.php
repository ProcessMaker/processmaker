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
            <div class="col-12">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">


                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab"
                           aria-controls="nav-home" aria-selected="true">Information</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab"
                           aria-controls="nav-profile" aria-selected="false">Permissions</a>
                        <a class="nav-item nav-link" id="nav-tokens-tab" data-toggle="tab" href="#nav-tokens" role="tab"
                           aria-controls="nav-tokens" aria-selected="false">API Tokens</a>

                    </div>
                </nav>
                <div class="card card-body tab-content mt-3" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">

                        {{--=========================--}}

                        <div class="row">
                            <div class="col-8">
                                <div class="card card-body">
                                    <h2>{{__('Name')}}</h2>
                                    <div class="row">
                                        <div class="form-group col">
                                            {!! Form::label('firstname', 'First Name') !!}
                                            {!! Form::text('firstname', null, ['id' => 'firstname','class'=> 'form-control', 'v-model' => 'formData.firstname',
                                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.firstname}']) !!}
                                            <div class="invalid-feedback" v-if="errors.firstname">@{{errors.firstname[0]}}</div>
                                        </div>
                                        <div class="form-group col">
                                            {!! Form::label('lastname', 'Last Name') !!}
                                            {!! Form::text('lastname', null, ['id' => 'lastname', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                                            => 'formData.lastname', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.lastname}']) !!}
                                            <div class="invalid-feedback" v-if="errors.lastname">@{{errors.description[0]}}</div>
                                        </div>
                                    </div>
                                    <h2 class="mt-2">{{__('Contact Information')}}</h2>
                                    <div class="row">
                                        <div class="form-group col">
                                            {!! Form::label('email', 'Email') !!}
                                            {!! Form::email('email', null, ['id' => 'email', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                                            => 'formData.email', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.email}']) !!}
                                            <div class="invalid-feedback" v-if="errors.email">@{{errors.email[0]}}</div>
                                        </div>
                                        <div class="form-group col">
                                            {!! Form::label('phone', 'Phone') !!}
                                            {!! Form::text('phone', null, ['id' => 'phone','class'=> 'form-control', 'v-model' => 'formData.phone',
                                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.phone}']) !!}
                                            <div class="invalid-feedback" v-if="errors.phone">@{{errors.phone[0]}}</div>
                                        </div>
                                    </div>
                                    <h2 class="mt-2">{{__('Address')}}</h2>
                                    <div class="row">
                                        <div class="form-group col">
                                            {!! Form::label('address', 'Address') !!}
                                            {!! Form::text('address', null, ['id' => 'address','class'=> 'form-control', 'v-model' => 'formData.address',
                                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.address}']) !!}
                                            <div class="invalid-feedback" v-if="errors.address">@{{errors.address[0]}}</div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col">
                                            {!! Form::label('city', 'City') !!}
                                            {!! Form::text('city', null, ['id' => 'city', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                                            => 'formData.city', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.city}']) !!}
                                            <div class="invalid-feedback" v-if="errors.city">@{{errors.city[0]}}</div>
                                        </div>
                                        <div class="form-group col">
                                            {!! Form::label('state', 'State or Region') !!}
                                            {!! Form::select('state',
                                                    $states,
                                                    'formData.state',
                                                    ['id' => 'state',
                                                        'class'=> 'form-control',
                                                        'v-model' => 'formData.state',
                                                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.state}'
                                                    ])
                                             !!}
                                            <div class="invalid-feedback" v-if="errors.state">@{{errors.state[0]}}</div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col">
                                            {!! Form::label('postal', 'Postal code') !!}
                                            {!! Form::text('postal', null, ['id' => 'postal', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                                            => 'formData.postal', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.postal}']) !!}
                                            <div class="invalid-feedback" v-if="errors.postal">@{{errors.postal[0]}}</div>
                                        </div>
                                        <div class="form-group col">
                                            {!! Form::label('country', 'Country') !!}
                                            {!! Form::select('country',
                                                $countries,
                                                'formData.country',
                                                ['id' => 'country',
                                                    'class'=> 'form-control',
                                                    'v-model' => 'formData.country',
                                                    'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.country}'
                                                ])
                                             !!}
                                            <div class="invalid-feedback" v-if="errors.country">@{{errors.country[0]}}</div>
                                        </div>
                                    </div>
                                    <h2 class="mt-2">{{__('Localization')}}</h2>
                                    <div class="row">
                                        <div class="form-group col">
                                            {!!Form::label('datetime_format', 'Date format');!!}
                                            {!!Form::select('datetime_format',
                                                $datetimeFormats,
                                                'formData.datetime_format',
                                                ['id' => 'datetime_format',
                                                        'class' => 'form-control',
                                                        'v-model' => 'formData.datetime_format',
                                                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.datetime_format}'
                                                ])
                                            !!}
                                            <div class="invalid-feedback" v-if="errors.email">@{{errors.datetime_format[0]}}</div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col">
                                            {!!Form::label('timezone', 'Time zone');!!}
                                            {!!Form::select('timezone',
                                                $timezones,
                                                'formData.timezone',
                                                 ['id'=>'timezone',
                                                    'class'=> 'form-control',
                                                    'v-model'=> 'formData.timezone',
                                                    'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.datetimeFormat}'
                                                 ])
                                             !!}
                                            <div class="invalid-feedback" v-if="errors.email">@{{errors.timezone[0]}}</div>
                                        </div>

                                        <div class="form-group col">
                                            {!! Form::label('language', 'Language') !!}
                                            {!! Form::select('language', ['us_en' => 'us_en'], $user->language,
                                            ['id' =>
                                            'language','class'=>
                                            'form-control',
                                            'v-model' => 'formData.language',
                                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.language}']) !!}
                                            <div class="invalid-feedback" v-if="errors.language">@{{errors.language[0]}}</div>
                                        </div>
                                    </div>
                                    {{--<div class="text-right">--}}
                                        {{--{!! Form::button('Cancel', ['class'=>'btn btn-outline-success', '@click' => 'onClose']) !!}--}}
                                        {{--{!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onUpdate']) !!}--}}
                                    {{--</div>--}}

                                    <div class="text-right">
                                        {!! Form::button('Cancel', ['class'=>'btn btn-outline-success', '@click' => 'onClose']) !!}
                                        {!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onProfileUpdate']) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-body">

                                    <div align="center" data-toggle="modal" data-target="#exampleModal">
                                        <avatar-image size="150" class-image="m-1"
                                                      :input-data="options"></avatar-image>
                                    </div>
                                    <div class="form-group">
                                        {!! Form::label('username', 'Username') !!}
                                        {!! Form::text('username', null, ['id' => 'username', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                                        => 'formData.username', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.username}']) !!}
                                        <div class="invalid-feedback" v-if="errors.username">@{{errors.username[0]}}</div>
                                    </div>

                                    <div class="form-group">
                                        {!!Form::label('status', 'Status') !!}
                                        {!!Form::select('size', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], 'formData.status', ['class'=> 'form-control', 'v-model'=> 'formData.status',
                                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}']);!!}
                                        <div class="invalid-feedback" v-if="errors.email">@{{errors.status[0]}}</div>
                                    </div>

                                    <div class="form-group">
                                        {!! Form::label('password', 'New Password') !!}
                                        {!! Form::password('password', ['id' => 'password', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                                        => 'formData.password', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}']) !!}
                                        <div class="invalid-feedback" v-if="errors.password">@{{errors.password[0]}}</div>
                                    </div>
                                    <div class="form-group">
                                        {!! Form::label('confPassword', 'Confirm confPassword') !!}
                                        {!! Form::password('confPassword', ['id' => 'confPassword', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                                        => 'formData.confPassword', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.confPassword}']) !!}
                                        <div class="invalid-feedback" v-if="errors.confPassword">@{{errors.confPassword[0]}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-----------------------------}}

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

                    <div class="tab-pane fade" id="nav-tokens" role="tabpanel" aria-labelledby="nav-tokens-tab">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Created At</th>
                                <th>Expires At</th>
                                <th>Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="token in apiTokens">
                                <td>@{{ token.id.substr(0,7) }}</td>
                                <td>@{{ moment(token.created_at).format() }}</td>
                                <td>@{{ moment(token.expires_at).format() }}</td>
                                <td>
                                    <a style="cursor: pointer" @click='deleteToken(token.id)'>
                                        <i class="fas fa-trash-alt fa-lg" style="cursor: pointer"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr v-if='apiTokens.length == 0'>
                                <td colspan="4">User has no tokens.</td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="form-group" v-if="newToken != null">
                            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Make sure you copy your access token now. You won't be able to see it again.</div>
                            <button @click="copyTextArea" class="btn btn-secondary"><i class="fas fa-paste"></i> Copy Token To Clipboard</button>
                            <textarea ref="text" style="height: 400px" class="form-control">@{{ newToken.accessToken }}</textarea>
                        </div>
                        <hr class="mt-0">
                        <button class="btn btn-secondary float-right" @click="generateToken">Generate New Token</button>
                    </div>


                    <div class="tab-pane fade" id="nav-test" role="tabpanel" aria-labelledby="nav-test-tab">
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
                        <div class="text-right">
                            {!! Form::button('Cancel', ['class'=>'btn btn-outline-success', '@click' => 'onClose']) !!}
                            {!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onProfileUpdate']) !!}
                        </div>
                        {!! Form::close() !!}

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
                            newToken: null,
                            apiTokens: [],
                            options: [
                                {
                                    src: @json($user['avatar']),
                                    title: @json($user['fullname']),
                                    initials: @json($user['firstname'][0]) + @json($user['lastname'][0])
                                }
                            ]
                        }
                    },
                    beforeMount() {
                        this.isSuperUser()
                    },
                    created() {
                        this.hasPermission()
                    },
                    mounted() {
                        this.loadTokens();
                    },
                    methods: {
                        copyTextArea() {
                            this.$refs.text.select();
                            document.execCommand('copy');
                        },
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
                            if (this.formData.password !== this.formData.confPassword) {
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
                                    ProcessMaker.alert('{{__('Update User Successfully')}}', 'success');
                                    that.onClose();
                                })
                                .catch(error => {
                                    ProcessMaker.alert('{{__('An error occurred while saving the Groups.')}}', 'danger');
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
                        loadTokens() {
                            ProcessMaker.apiClient({method: 'GET', url: '/oauth/personal-access-tokens', baseURL: '/'})
                                .then((result) => {
                                    this.apiTokens = result.data
                                })
                        },
                        generateToken() {
                            ProcessMaker.apiClient({
                                method: 'POST',
                                url: '/oauth/personal-access-tokens',
                                baseURL: '/',
                                data: { name: 'API Token', scopes: [] }
                            })
                                .then((result) => {
                                    this.newToken = result.data;
                                    this.loadTokens();
                                    ProcessMaker.alert("Access token generated successfully", "success");
                                })
                        },
                        deleteToken(tokenId) {
                            ProcessMaker.confirmModal(
                                "Caution!",
                                "Are you sure to delete the token " + tokenId.substr(0,7) + "? Any services using it will no longer have access.",
                                "",
                                () => {
                                    ProcessMaker.apiClient({
                                        method: 'DELETE',
                                        url: '/oauth/personal-access-tokens/' + tokenId,
                                        baseURL: '/',
                                    })
                                        .then((result) => {
                                            this.loadTokens();
                                            this.newToken = null;
                                        })
                                }
                            );
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
