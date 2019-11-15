@extends('layouts.layout')

@section('title')
    {{__('Users')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Users') => null,
    ]])
@endsection
@section('content')
    <div class="px-3 page-content" id="users-listing">
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
                @can('create-users')
                    <button type="button" id="addUserBtn" class="btn btn-action text-light" @click="$refs.addUser.show()">
                        <i class="fas fa-plus"></i>
                        {{__('User')}}
                    </button>
                @endcan
            </div>
        </div>
        <div class="container-fluid">
            <users-listing ref="listing" :filter="filter" :permission="{{ \Auth::user()->hasPermissionsFor('users') }}"
                           v-on:reload="reload"></users-listing>
        </div>
        
        @can('create-users')
            <b-modal hidden 
                     ref="addUser" 
                     title="{{ __('Create User') }}" 
                     ok-title="{{ __('Save') }}" 
                     @ok="onSubmit" 
                     @hidden="onClose"
            >
                
                <div class="form-group">
                    {!!Form::label('username', __('Username'))!!}<small class="ml-1">*</small>	
                    {!!Form::text('username', null, ['class'=> 'form-control', 'v-model'=> 'addUser.username', 'v-bind:class'	
                    => '{\'form-control\':true, \'is-invalid\':addUser.addError.username}', 'autocomplete' => 'off']) !!}

                    <div class="invalid-feedback" v-for="username in addUser.addError.username">
                        <div v-if="username !== 'userExists'">
                            @{{ username }}
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    {!!Form::label('firstname', __('First Name'))!!}<small class="ml-1">*</small>   
                    {!!Form::text('firstname', null, ['class'=> 'form-control', 'v-model'=> 'addUser.firstname', 'v-bind:class'
                    => '{\'form-control\':true, \'is-invalid\':addUser.addError.firstname}'])!!}
                    <div class="invalid-feedback" v-for="firstname in addUser.addError.firstname">@{{firstname}}</div>
                </div>

                <div class="form-group">
                    {!!Form::label('lastname', __('Last Name'))!!}<small class="ml-1">*</small>
                    {!!Form::text('lastname', null, ['class'=> 'form-control', 'v-model'=> 'addUser.lastname', 'v-bind:class'
                    => '{\'form-control\':true, \'is-invalid\':addUser.addError.lastname}'])!!}
                    <div class="invalid-feedback" v-for="lastname in addUser.addError.lastname">@{{lastname}}</div>
                </div>

                <div class="form-group">
                    {!!Form::label('title', __('Job Title'))!!}
                    {!!Form::text('title', null, ['class'=> 'form-control', 'v-model'=> 'addUser.title', 'v-bind:class'
                    => '{\'form-control\':true, \'is-invalid\':addUser.addError.title}'])!!}
                    <div class="invalid-feedback" v-for="title in addUser.addError.title">@{{title}}</div>	
                </div>

                <div class="form-group">
                    {!!Form::label('status', __('Status'));!!}<small class="ml-1">*</small>
                    {!!Form::select('size',[null => __('Select')]+['ACTIVE' => __('Active'), 'INACTIVE' => __('Inactive')], 'Active', [
                    'class'=> 'form-control', 'v-model'=> 'addUser.status', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addUser.addError.status}']);!!}
                    <div class="invalid-feedback" v-for="status in addUser.addError.status">@{{status}}</div>
                </div>

                <div class="form-group">
                    {!!Form::label('email', __('Email'))!!}<small class="ml-1">*</small>
                    {!!Form::email('email', null, ['class'=> 'form-control', 'v-model'=> 'addUser.email', 'v-bind:class' =>
                    '{\'form-control\':true, \'is-invalid\':addUser.addError.email}', 'autocomplete' => 'off'])!!}
                    <div class="invalid-feedback" v-for="email in addUser.addError.email">
                        <div v-if="email !== 'userExists'">
                            @{{ email }}
                        </div>
                    </div>
                </div>

                <div class="form-group">	
                    {!!Form::label('password', __('Password'))!!}<small class="ml-1">*</small>	
                    <vue-password v-model="addUser.password" :disable-toggle=true ref="passwordStrength">	
                        <div slot="password-input" slot-scope="props">	
                            {!!Form::password('password', ['class'=> 'form-control', 'v-model'=> 'addUser.password',	
                            '@input' => 'props.updatePassword($event.target.value)', 'autocomplete' => 'new-password',	
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addUser.addError.password}'])!!}	
                        </div>	
                    </vue-password>	
                </div>	

                <div class="form-group">	
                    {!!Form::label('confpassword', __('Confirm Password'))!!}<small class="ml-1">*</small>	
                    {!!Form::password('confpassword', ['class'=> 'form-control', 'v-model'=> 'addUser.confpassword',	
                    'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addUser.addError.password}', 'autocomplete' => 'new-password'])!!}	
                    <div class="invalid-feedback" v-for="password in addUser.addError.password">@{{password}}</div>	
                </div>
            </b-modal>

            {{-- <div class="modal" tabindex="-10" role="dialog" id="restoreUser">
                <div class="modal-dialog modal-dialog-centered" rold="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Deleted User Found') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">	
                                <span aria-hidden="true">&times;</span>	
                            </button>
                        </div>

                        <div class="modal-body">
                            <p>{{ __('An existing user has been found with the ')}} @{{ restoreAttribute + ' "' + restoreValue + '"' }} {{__(' would you like to save and reactivate their account?') }}</p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" @click="onCancelRestore">	
                                {{ __('Cancel') }}
                            </button>

                            <button type="button" class="btn btn-secondary ml-2" @click="onSaveRestore">	
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div> --}}
        @endcan
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/users/index.js')}}"></script>

    @can('create-users')	
    <script>	
    
    // var restoreUserModal = new Vue({	
    //     el: '#restoreUser',	
    //     data(){	
    //         return {	
    //             restoreValue: '',	
    //             restoreAttribute: '',	
    //             emailToRestore: '',	
    //             usernameToRestore: ''	
    //         }	
    //     },	
    //     methods: {	
    //         onCancelRestore() {	
    //             $('#restoreUser').modal('hide');	
    //         },	
    //         onSaveRestore() {	
    //             let data = [];	
    //             if (this.emailToRestore !== '') {	
    //                 this.data = {	
    //                     email: this.emailToRestore	
    //                 };	
    //             }	
    //             if (this.usernameToRestore !== '') {	
    //                 this.data = {	
    //                     username: this.usernameToRestore	
    //                 };	
    //             }	
    //             ProcessMaker.apiClient.put('/users/restore', this.data)	
    //             .then(response => {	
    //                 $('#restoreUser').modal('hide');	
    //                 ProcessMaker.alert(this.$t("The user was restored."), "success");	
    //                 location.reload();	
    //             })	
    //             .catch(error => {	
    //                 ProcessMaker.alert(error, "danger");	
    //             });	
    //         }	
    //     },	
    // });	
    </script>	
@endcan

    
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
    </style>
@endsection
