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
        @include('admin.users.form')
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