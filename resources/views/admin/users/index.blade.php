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
                    <button type="button" class="btn btn-action text-light" data-toggle="modal" data-target="#addUser"><i
                            class="fas fa-plus"></i> {{__('User')}}</button>
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
                    {!!Form::text('username', null, ['class'=> 'form-control', 'v-model'=> 'username', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.username}'])!!}
                    <div class="invalid-feedback" v-for="username in addError.username">@{{username}}</div>
                </div>
                <div class="form-group">
                    {!!Form::label('firstname', __('First name'))!!}
                    {!!Form::text('firstname', null, ['class'=> 'form-control', 'v-model'=> 'firstname', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.firstname}'])!!}
                    <div class="invalid-feedback" v-for="firstname in addError.firstname">@{{firstname}}</div>
                </div>
                <div class="form-group">
                    {!!Form::label('lastname', __('Last name'))!!}
                    {!!Form::text('lastname', null, ['class'=> 'form-control', 'v-model'=> 'lastname', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.lastname}'])!!}
                    <div class="invalid-feedback" v-for="lastname in addError.lastname">@{{lastname}}</div>
                </div>
                <div class="form-group">
                    {!!Form::label('email', __('Email'))!!}
                    {!!Form::email('email', null, ['class'=> 'form-control', 'v-model'=> 'email', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.email}'])!!}
                    <div class="invalid-feedback" v-for="email in addError.email">@{{email}}</div>
                </div>
                <div class="form-group">
                    {!!Form::label('password', __('Password'))!!}
                    {!!Form::password('password', ['class'=> 'form-control', 'v-model'=> 'password', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.password}'])!!}
                    <div class="invalid-feedback" v-for="password in addError.password">@{{password}}</div>
                </div>
                <div class="form-group">
                    {!!Form::label('confpassword', __('Confirm Password'))!!}
                    {!!Form::password('confpassword', ['class'=> 'form-control', 'v-model'=> 'confpassword', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.password}'])!!}

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{__('Close')}}</button>
                <button type="button" class="btn btn-secondary" @click="onSubmit" id="disabledForNow">{{__('Save')}}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
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
        },
        methods: {
            onSubmit() {
                this.submitted = true;
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
    })
</script>
<script src="{{mix('js/admin/users/index.js')}}"></script>
@endsection