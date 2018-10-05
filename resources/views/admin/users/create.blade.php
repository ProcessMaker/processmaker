@extends('layouts.layout')

@section('title')
  {{__('Add a User')}}
@endsection

@section('content')
<div class="container" id="createUser">
<create-user></create-user>
</div>
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('js')
  <script src="{{mix('js/admin/users/index.js')}}"></script>
@endsection