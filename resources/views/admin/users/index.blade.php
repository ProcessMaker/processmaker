@extends('layouts.layout', ['title' => 'User Management'])

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_admin])
@endsection

@section('content')
    <div class="container">
    <div class="row">
        <div class="col-sm-12">
            <h1 class="panel-title">Users</h1>
            <div id="users-listing"></div>
        </div>
    </div>
    </div>
@endsection

@section('js')
<script src="{{mix('js/admin/users.js')}}"></script>
@endsection
