@extends('layouts.layout')

@section('content')
<div id="requests" class="container ml-2">
  <div class="row">
    <div class="col-sm">
      <h1>Requests</h1>
    </div>
  </div>
</div>
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('js')
@endsection
