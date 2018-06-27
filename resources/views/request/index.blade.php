@extends('layouts.layout')

@section('content')
<div id="requests" class="container ml-2">
  <div class="row">
    <div class="col-sm">
      <p>We've made it easy for you to make the following requests</p>
    </div>
    <div class="col-sm text-right">
      <input v-model="filter" placeholder="Search...">
    </div>
  </div>
  <div class="row">
    <div class="col-sm">
      <h3>Test Category</h3>
      <process-card title="Lorem Ipsum" description="This is a test description of an example process"></process-card>
    </div>
  </div>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_request])
@endsection

@section('js')
@endsection
