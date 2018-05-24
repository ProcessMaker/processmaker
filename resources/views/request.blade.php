@extends('layouts.layout')

@section('content')
<div class="container ml-2">
  <h1>Request</h1>


</div>
@endsection

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_request])
@endsection

@section('js')

@endsection
