@extends('layouts.layout')

@section('content')
<div class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4" id="main">
  <div class="panel-heading">Dashboard</div>

  <div class="panel-body">
    @if (session('status'))
      <div class="alert alert-success">
        {{ session('status') }}
      </div>
    @endif

    You are logged in!
  </div>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection
