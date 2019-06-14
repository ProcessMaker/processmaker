@extends('layouts.layout')

@section('title')
  {{__('Server Error - ProcessMaker')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_designer')])
@endsection

@section('content')
 <div class="container mr-5 mt-5">
     <img class="ml-5" src="/img/robot.png"/>
    <h1>{{__('Server Error')}}</h1>
    <p>{{__('One of the data sources in unavailable. Contact with your system administrator.')}}</p>
</div>
@endsection

