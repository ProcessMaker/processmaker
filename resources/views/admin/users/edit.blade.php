@extends('layouts.layout')

@section('title')
  {{__('Edit Users')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div class="container">
  <h1>Edit User</h1>
  <div class="row">
    <div class="col-8">
      <div class="card card-body">
        <div class="modal-body" id="editUser">
        {!! Form::open() !!}
          <div class="form-group">
            {!! Form::label('username', 'Username') !!}
            {!! Form::text('username', $user->username, ['class'=> 'form-control', 'v-model' => 'username']) !!}
            <div class="text-danger" v-if="addError.username">@{{addError.title[0]}}</div>
            <small id="emailHelp" class="form-text text-muted">Username must be distinct</small>
          </div>
          <div class="form-group">
              {!! Form::label('firstname', 'First Name') !!}
              {!! Form::text('firstname', $user->firstname, ['class'=> 'form-control', 'v-model' => 'firstname']) !!}
              <div class="text-danger" v-if="addError.name">@{{addError.title[0]}}</div>
          </div>
          <div class="form-group">
              {!! Form::label('lastname', 'Last Name') !!}
              {!! Form::text('lastname', $user->lastname, ['class'=> 'form-control', 'v-model' => 'lastname']) !!}
          </div>
          <div class="form-group">
              {!! Form::label('email', 'Email') !!}
              {!! Form::text('email', $user->email, ['class'=> 'form-control', 'v-model' => 'email']) !!}
          </div>
          <div class="form-group">
              {!! Form::label('status', 'Status') !!}
              {!! Form::select('status', [ 'ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], $user->status, ['class' => 'form-control', 'v-model' => 'status']) !!}
          </div>
          <div class="form-group">
              {!! Form::label('password', 'Password') !!}
              {!! Form::password('password', ['class' => 'form-control']) !!}
          </div>
          <div class="form-group">
              {!! Form::label('password_confirm', 'Confirm Password') !!}
              {!! Form::password('password_confirm', ['class' => 'form-control']) !!}
          </div>
          <br>
          <div class="text-right">
          {!! Form::button('Cancel', ['class'=>'btn btn-outline-success']) !!}
          {!! Form::submit('Save', ['class'=>'btn btn-success ml-2']) !!}
        </div>
        {!! Form::close() !!}
      </div>
    </div>
    <div class="col-4">
      <div class="card card-body">
      Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
  new Vue ({
    el: '#editUser', 
    data() {
      return {
        existing: @json($user),
        username: @json($user->username),
        firstname: @json($user->firstname),
        lastname: @json($user->lastname),
        email: @json($user->email),
        status: @json($user->status),
        uuid: @json($user->uuid_text),
        existing: @json($user),
        addError: [],
      }
    },
    methods: {
      onUpdate(){
        console.log('update')
        console.log(this.existing);
        console.log(this.firstname);
        ProcessMaker.apiClient.put("/users/{{$user->uuid_text}}" , {
          firstname: this.firstname,
          lastname: this.lastname,
          email: this.email,
          status: this.status,
        })
        .then(response => {
          console.log(response);
          console.log('then');
          ProcessMaker.alert('User successfully updated', 'update');
          window.location = "/admin/users/" + response.data.uuid
        })
        .catch(error => {
          if (error.response.status === 422) {
            this.addError = error.response.data.errors
          }
        })
      }
    } 
  });
</script>
@endsection