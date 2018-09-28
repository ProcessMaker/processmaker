@extends('layouts.layout')
@section('content')
    <div id="script-container">
        {{-- <script-editor :process="{{$process}}" :script="{{$script}}"></script-editor> --}}
    </div>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_designer')])
@endsection

@section('js')
    <script src="{{ mix('js/designer/ScriptEditor/main.js') }}"></script>
@endsection

@section('css')
<style>
    #mainbody {
        overflow: hidden;
    }
</style>
@endsection
