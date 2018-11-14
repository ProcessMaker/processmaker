@extends('layouts.layout')

@section('title')
    {{__('Screens Builder')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div id="screen-container">
        <screen-builder :screen="{{$screen}}"></screen-builder>
    </div>
@endsection

@section('js')
    @foreach($manager->getScripts() as $script)
      <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/processes/screen-builder/main.js')}}"></script>
@endsection
