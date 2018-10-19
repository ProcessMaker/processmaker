@extends('layouts.layout')

@section('title')
  {{__('Scripts Editor')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<div class="container" id="script-container">
    <script-editor :script="{{$script}}"> </script-editor>
</div>
@endsection

@section('js')
    <script src="{{mix('js/designer/ScriptEditor/main.js')}}"></script>
@endsection

