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
            <div class="card card-body">
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
                    <multiselect v-model="value" :options="dataGroups" :multiple="true" track-by="name" :custom-label="customLabel"
                        :show-labels="false" label="name">

                        <template slot="tag" slot-scope="props">
                            <span class="multiselect__tag  d-flex align-items-center" style="width:max-content;">
                                <img class="option__image mr-1" :src="props.option.img" alt="Check it">
                                <span class="option__desc mr-1">@{{ props.option.name }}
                                    <span class="option__title">@{{ props.option.desc }}</span>
                                </span>
                                <i aria-hidden="true" tabindex="1" @click="props.remove(props.option)" class="multiselect__tag-icon"></i>
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
                    {!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onUpdate']) !!}
                </div>
                {!! Form::close() !!}
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
                }
            }
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
            onUpdate() {
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
                                ProcessMaker.apiClient
                                    .post('group_members', {
                                        'group_id': group.id,
                                        'member_type': 'ProcessMaker\\Models\\User',
                                        'member_id': that.formData.id
                                    });
                            }
                        });

                        ProcessMaker.alert('{{__('Update User Successfully')}}', 'success');
                        that.onClose();
                    })
                    .catch(error => {
                        //define how display errors
                        if (error.response.status && error.response.status === 422) {
                            // Validation error
                            that.errors = error.response.data.errors;
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