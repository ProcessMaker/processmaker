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
        {!! Form::model($user , ['route' => ['users.update', $user->uuid_text ]]) !!}
        <div class="form-group">
          {!! Form::label('username', 'Username') !!}
          {!! Form::text('username', null, ['class'=> 'form-control']) !!}
          <small id="emailHelp" class="form-text text-muted">Username must be distinct</small>
        </div>
        <div class="form-group">
          {!! Form::label('firstname', 'First Name') !!}
          {!! Form::text('firstname', null, ['class'=> 'form-control']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('lastname', 'Last Name') !!}
          {!! Form::text('lastname', null, ['class'=> 'form-control']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('email', 'Email') !!}
          {!! Form::text('email', null, ['class'=> 'form-control']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('status', 'Status') !!}
          {!! Form::select('status', ['Active', 'Inactive'], null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('password', 'Password') !!}
          {!! Form::password('password', ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('password_confirm', 'Confirm Password') !!}
          {!! Form::password('password', ['class' => 'form-control']) !!}
        </div>
        <div class="d-flex justify-content-end mt-2">
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
@endsection