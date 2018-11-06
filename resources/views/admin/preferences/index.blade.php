@extends('layouts.layout')

@section('title')
  {{__('Preferences')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container mt-4">
      <h3>Preferences</h3>
      <div class="card card-body">
        <div class="d-flex mb-2">
          <h3 class="mt-1">Localization</h3>
          <button class="btn btn-secondary ml-2 mt-1" style="height:22px; padding-bottom: 21px;"><i class="fas fa-undo-alt"></i>  Reset</button>
        </div>
        <div class="row">
          <div class="form-group col">
            {!!Form::label('fullName', 'Full Name Format');!!}
            {!!Form::select('size', ['firstName' => 'First Name', 'lastName' => 'Last Name'], null, ['class'=> 'form-control']);!!}
            <small id="emailHelp" class="form-text text-muted">Format to display user's full name across all applications</small>
          </div>
        </div>
        <div class="row">
          <div class="form-group col">
            {!!Form::label('defaultLang', 'Default Language');!!}
            {!!Form::select('size', ['english' => 'English'], null, ['class'=> 'form-control']);!!}
            <small id="emailHelp" class="form-text text-muted">Default Language to be used across all applications</small>
          </div>
        </div>
        <div class="d-flex mt-5 mb-2">
          <h3 class="mt-1">Email Notifications</h3>
          <button class="btn btn-secondary ml-2 mt-1" style="height:22px; padding-bottom: 21px;"><i class="fas fa-check"></i>  Test</button>
        </div>
        <div class="row">
          <div class="form-group col">
            {!!Form::label('hostName', 'Host Name');!!}
            {!!Form::text('hostName', null, ['class'=> 'form-control', 'placeholder'=> 'Host Name']);!!}
            <small id="emailHelp" class="form-text text-muted">Address to the SMTP server used for email notifications</small>
          </div>
          <div class="form-group col">
            {!!Form::label('userName', 'Username');!!}
            {!!Form::text('userName', null, ['class'=> 'form-control', 'placeholder'=> 'Email Username']);!!}
            <small id="emailHelp" class="form-text text-muted">Enter the account to be authenticated against the SMTP server</small>
          </div>
        </div>
        <div class="row">
          <div class="form-group col">
            {!!Form::label('serverPort', 'Server Port');!!}
            {!!Form::text('serverPort', null, ['class'=> 'form-control', 'placeholder'=> 'Server Port']);!!}
            <small id="emailHelp" class="form-text text-muted">SMTP service port. Default port 25 will be used if you leave blank</small>
          </div>
          <div class="form-group col">
            {!!Form::label('password', 'Password');!!}
            {!!Form::password('password', ['class' => 'form-control', 'placeholder'=> 'Password']);!!}
            <small id="emailHelp" class="form-text text-muted">Password used to authenticate the user</small>
          </div>
        </div>
        <div class="row">
          <div class="form-group form-check col">
            {!!Form::checkbox('sslTls', 'SSL/TLS');!!}
            {!!Form::label('sslTls', 'SSL/TLS');!!}
            <small id="emailHelp" class="form-text text-muted">Enable if SSL/TLS is required by this server</small>
          </div>
          <div class="form-group col">
            {!!Form::label('authMethod', 'Authentication Method');!!}
            {!!Form::select('size', ['SSL' => 'SSL', 'GSSAPI' => 'GSSAPI', 'NTLM'=> 'NTLM', 'MD5'=> 'MD5', 'password'=> 'password'],null, ['class'=> 'form-control']);!!}
            <small id="emailHelp" class="form-text text-muted">Authentication protocol user to login to the SMTP server</small>
          </div>
        </div>
        <div class="row mt-4" align="right">
          <div class="form-group col">
            <button class="btn btn-outline-success">Cancel</button>
            <button class="btn btn-success">Save</button>
          </div>
        </div>
      </div>
    </div>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_admin')])
@endsection

@section('js')
  <script src="{{mix('js/admin/preferences/index.js')}}"></script>
@endsection