@extends('layouts.layout')

@section('content')
<div class="container ml-2">
  <h1>Task</h1>


</div>
@endsection

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_task])
@endsection

@section('js')

@endsection
