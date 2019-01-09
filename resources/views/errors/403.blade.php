@extends('layouts.layout')

@section('title')
  {{__('About ProcessMaker 4')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_designer')])
@endsection

@section('content')
 <div class="container mr-5 mt-5">
     <img class="ml-5" src="/img/robot.png"/>
    <h1>{{__('Not Authorized')}}</h1>
    <p>{{__('See the Permissions page to make changes')}}</p>
</div>
@endsection

