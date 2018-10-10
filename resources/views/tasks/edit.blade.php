@extends('layouts.layout')

@section('title')
  {{__('Edit Task')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    
@endsection

@section('js')
    <script src="{{mix('js/processes/tasks/index.js')}}"></script>
@endsection
