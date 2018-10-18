@extends('layouts.layout')

@section('title')
  {{__('Scripts Editor')}}
@endsection

@Section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@Section('content')
<div class="container" id="script-container">
    <script-editor :script="{{$script}}"> </script-editor>
</div>
@endsection

@Section('js')
    <script src="{{mix('js/designer/ScriptEditor/main.js')}}"></script>
@endsection

