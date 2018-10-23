@extends('layouts.layout')

@section('title')
    {{__('Users')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" id="users-listing" v-cloak>
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Users')}}</h1>
                        <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <button type="button" class="btn btn-action text-light" data-toggle="modal"
                                data-target="#addUser"><i class="fas fa-plus"></i>
                            {{__('User')}}</button>
                    </div>
                </div>
                <users-listing ref="listing" :filter="filter" v-on:reload="reload"></users-listing>
            </div>
        </div>
    </div>
    <div class="modal" tabindex="-1" role="dialog" id="addUser">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Add A User')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!!Form::label('username', __('Username'))!!}
                        {!!Form::text('username', null, ['class'=> 'form-control', 'v-model'=> 'username', 'v-bind:class'
                        => '{\'form-control\':true, \'is-invalid\':addError.username}'])!!}
                        <div class="invalid-feedback" v-for="username in addError.username">@{{username}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('firstname', __('First name'))!!}
                        {!!Form::text('firstname', null, ['class'=> 'form-control', 'v-model'=> 'firstname', 'v-bind:class'
                        => '{\'form-control\':true, \'is-invalid\':addError.firstname}'])!!}
                        <div class="invalid-feedback" v-for="firstname in addError.firstname">@{{firstname}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('lastname', __('Last name'))!!}
                        {!!Form::text('lastname', null, ['class'=> 'form-control', 'v-model'=> 'lastname', 'v-bind:class'
                        => '{\'form-control\':true, \'is-invalid\':addError.lastname}'])!!}
                        <div class="invalid-feedback" v-for="lastname in addError.lastname">@{{lastname}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('email', __('Email'))!!}
                        {!!Form::email('email', null, ['class'=> 'form-control', 'v-model'=> 'email', 'v-bind:class' =>
                        '{\'form-control\':true, \'is-invalid\':addError.email}'])!!}
                        <div class="invalid-feedback" v-for="email in addError.email">@{{email}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('password', __('Password'))!!}
                        {!!Form::password('password', ['class'=> 'form-control', 'v-model'=> 'password', 'v-bind:class' =>
                        '{\'form-control\':true, \'is-invalid\':addError.password}'])!!}
                        <div class="invalid-feedback" v-for="password in addError.password">@{{password}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('confpassword', __('Confirm Password'))!!}
                        {!!Form::password('confpassword', ['class'=> 'form-control', 'v-model'=> 'confpassword',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.password}'])!!}

                    </div>
                    <div class="form-group">
                        {!!Form::label('groups', __('Groups'))!!}
                        <multiselect v-model="value" :options="dataGroups" :multiple="true" track-by="title"
                                     :custom-label="customLabel" :show-labels="false"
                                     label="name">

                            <template slot="tag" slot-scope="props">
                            <span class="multiselect__tag  d-flex align-items-center" style="width:max-content;">
                                <img class="option__image mr-1" :src="props.option.img" alt="Check it">
                                <span class="option__desc mr-1">@{{ props.option.name }}
                                    <span class="option__title">@{{ props.option.desc }}</span>
                                </span>
                                <i aria-hidden="true" tabindex="1" class="multiselect__tag-icon"></i>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                            data-dismiss="modal">{{__('Close')}}</button>
                    <button type="button" class="btn btn-secondary" @click="onSubmit"
                            id="disabledForNow">{{__('Save')}}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/users/index.js')}}"></script>

    <script>
        new Vue({
            el: '#addUser',
            data: {
                username: '',
                firstname: '',
                lastname: '',
                email: '',
                password: '',
                confpassword: '',
                addError: {},
                submitted: false,
                value: [],
                options: @json($groups),
                dataGroups: []
            },
            mounted() {
                this.fillDataGroups(this.options);
            },
            methods: {
                fillDataGroups(data) {
                    let that = this;
                    that.dataGroups = [];
                    let values = [];
                    $.each(data, function (key, value) {
                        let option = value;
                        option.img = '/img/avatar-placeholder.gif';
                        option.desc = ' ';
                        that.dataGroups.push(option);
                    });
                    that.value = values;
                },
                customLabel(options) {
                    return ` ${options.img} ${options.title} ${options.desc} `
                },
                validatePassword() {
                    if (this.password !== this.confpassword) {
                        this.addError.password = ['Passwords must match']
                        this.password = ''
                        this.submitted = false
                        return false
                    }
                    return true
                },
                onSubmit() {
                    this.submitted = true;
                    if (this.validatePassword()) {
                        ProcessMaker.apiClient.post("/users", {
                            username: this.username,
                            firstname: this.firstname,
                            lastname: this.lastname,
                            email: this.email,
                            password: this.password,
                        })
                            .then(response => {
                                ProcessMaker.alert('{{__('User successfully added ')}}', 'success')
                                window.location = "/admin/users/" + response.data.uuid + '/edit'
                            })
                            .catch(error => {
                                if (error.response.status === 422) {
                                    this.addError = error.response.data.errors
                                }
                            })
                            .finally(() => {
                                this.submitted = false
                            })
                    }
                }
            }
        })
    </script>
@endsection
@section('css')
    <style>
        /* .multiselect__tag {
          background: #788793 !important;
        } */
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

        /* .multiselect__tag-icon:focus, .multiselect__tag-icon:hover {
           background: #788793 !important;
        } */
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

        /* .multiselect__tag-icon:focus, .multiselect__tag-icon:hover {
           background: gray !important;
       } */
        /* .multiselect__option--highlight {
            background: #00bf9c !important;
        } */
        /* .multiselect__option--selected.multiselect__option--highlight {
            background: #00bf9c !important;
        } */
        /* .multiselect__tags {
            border: 1px solid #b6bfc6 !important;
            border-radius: 0.125em !important;
            height: calc(1.875rem + 2px) !important;
        } */
    </style>
@endsection