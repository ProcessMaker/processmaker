@extends('layouts.layout')

@section('content')
<div class="container" id="createUser">
  <h1>Hello</h1>
<create-user></create-user>
</div>
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('js')
  <script src="{{mix('js/admin/users/index.js')}}"></script>
@endsection