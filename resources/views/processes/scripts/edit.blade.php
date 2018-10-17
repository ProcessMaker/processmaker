@extends('layouts.layout')

@section('title')
  {{__('Scripts Editor')}}
@endsection

<<<<<<< HEAD
@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
@endsection

@section('js')
@endsection
=======
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

>>>>>>> feature/650
