@extends('layouts.layout')
@section('content')
    <div class="pmdesigner-container" id="appDesigner">
        <toolbar></toolbar>
        <designer ref="canvas"></designer>
    </div>
@endsection

@section('sidebar')
    @include('sidebars.default')
@endsection

@section('js')
    <script src="{{ asset('js/AppDesigner.js') }}"></script>
@endsection
