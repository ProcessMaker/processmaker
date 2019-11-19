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
        <div id="search-bar" class="search" vcloak>
            <div class="d-flex flex-column flex-md-row">
                <div class="flex-grow-1">
                    <div id="search" class="mb-3 mb-md-0">
                        <div class="input-group w-100">
                            <input v-model="filter" class="form-control" placeholder="{{__('Search')}}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                @can('create-users')
                    <div class="d-flex ml-md-2 flex-column flex-md-row">
                        <b-button @click="showModal()"> 
                            <i class="fas fa-plus"></i>
                            {{__('User')}}
                        </b-button>
                    </div>
                @endcan
            </div>
        </div>
        <div class="container-fluid">
            <users-listing ref="listing" :filter="filter" :permission="{{ \Auth::user()->hasPermissionsFor('users') }}"
                           v-on:reload="reload"></users-listing>
        </div>
        @can('create-users')
        <add-user-modal ref="addUserModal">
                <template v-slot:modal-title>
                    <h5>{{__('Create User')}}</h5>
                </template>
    
                <template v-slot:default>
                    <div class="form-group">
                        {!!Form::label('username', __('Username'))!!}<small class="ml-1">*</small>
                        {!!Form::text('username', null, ['class'=> 'form-control', 'v-model'=> 'config.username', 'v-bind:class'
                        => '{\'form-control\':true, \'is-invalid\':config.addError.username}', 'autocomplete' => 'off']) !!}
                        <div class="invalid-feedback" v-for="username in config.addError.username">
                            <div v-if="username !== 'userExists'"> 
                                @{{username}} 
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('firstname', __('First Name'))!!}<small class="ml-1">*</small>
                        {!!Form::text('firstname', null, ['class'=> 'form-control', 'v-model'=> 'config.firstname', 'v-bind:class'
                        => '{\'form-control\':true, \'is-invalid\':config.addError.firstname}'])!!}
                        <div class="invalid-feedback" v-for="firstname in config.addError.firstname">@{{firstname}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('lastname', __('Last Name'))!!}<small class="ml-1">*</small>
                        {!!Form::text('lastname', null, ['class'=> 'form-control', 'v-model'=> 'config.lastname', 'v-bind:class'
                        => '{\'form-control\':true, \'is-invalid\':config.addError.lastname}'])!!}
                        <div class="invalid-feedback" v-for="lastname in config.addError.lastname">@{{lastname}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('title', __('Job Title'))!!}
                        {!!Form::text('title', null, ['class'=> 'form-control', 'v-model'=> 'config.title', 'v-bind:class'
                        => '{\'form-control\':true, \'is-invalid\':config.addError.title}'])!!}
                        <div class="invalid-feedback" v-for="title in config.addError.title">@{{title}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('status', __('Status'));!!}<small class="ml-1">*</small>
                        {!!Form::select('size',[null => __('Select')]+['ACTIVE' => __('Active'), 'INACTIVE' => __('Inactive')], 'Active',
                        [
                        'class'=> 'form-control', 'v-model'=> 'config.status',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':config.addError.status}']);!!}
                        <div class="invalid-feedback" v-for="status in config.addError.status">@{{status}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('email', __('Email'))!!}<small class="ml-1">*</small>
                        {!!Form::email('email', null, ['class'=> 'form-control', 'v-model'=> 'config.email', 'v-bind:class' =>
                        '{\'form-control\':true, \'is-invalid\':config.addError.email}', 'autocomplete' => 'off'])!!}
                        <div class="invalid-feedback" v-for="email in config.addError.email">
                            <div v-if="email !== 'userExists'">
                                @{{email}}
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('password', __('Password'))!!}<small class="ml-1">*</small>
                        <vue-password v-model="config.password" :disable-toggle=true ref="passwordStrength">
                            <div slot="password-input" slot-scope="props">
                                {!!Form::password('password', ['class'=> 'form-control', 'v-model'=> 'config.password',
                                '@input' => 'props.updatePassword($event.target.value)', 'autocomplete' => 'new-password',
                                'v-bind:class' => '{\'form-control\':true, \'is-invalid\':config.addError.password}'])!!}
                            </div>
                        </vue-password>
                    </div>
                    <div class="form-group">
                        {!!Form::label('confpassword', __('Confirm Password'))!!}<small class="ml-1">*</small>
                        {!!Form::password('confpassword', ['class'=> 'form-control', 'v-model'=> 'config.confpassword',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':config.addError.password}', 'autocomplete' => 'new-password'])!!}
                        <div class="invalid-feedback" v-for="password in config.addError.password">@{{password}}</div>
                    </div>
                </template>
    
                <template v-slot:modal-footer>
                    <div class="text-right pb-5 mt-3">
                        <button type="button" class="btn btn-outline-secondary" @click="hideModal">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="onSubmit" :disabled="config.disabled">
                            {{__('Save')}}
                        </button>
                    </div>
                </template>
            </add-user-modal>
        @endcan
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/users/index.js')}}"></script>
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
