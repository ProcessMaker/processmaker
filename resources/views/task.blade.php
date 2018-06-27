@extends('layouts.layout')

@section('content')

  <h1>Task</h1>


@endsection

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_task])
@endsection

@section('js')

@endsection
