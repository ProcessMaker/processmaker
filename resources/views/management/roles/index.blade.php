@extends('layouts.layout', ['title' => 'Role Management'])

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_admin])
@endsection

@section('content')
    <div class="container">
    <div class="row">
        <div class="col-sm-12">
            <h1 class="panel-title">Roles</h1>
            <div id="roles-listing"></div>
        </div>
    </div>
    </div>
@endsection

@section('js')
<script src="{{mix('js/management/roles.js')}}"></script>
@endsection
