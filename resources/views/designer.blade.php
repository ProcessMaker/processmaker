@extends('layouts.layout')
@section('content')
    <link href="{{ asset('css/vendorDesigner.css') }}" rel="stylesheet">
    <div class="pmdesigner-container" id="appDesigner">
        <toolbar></toolbar>
        <designer ref="canvas"></designer>
    </div>
@endsection

@section('js')
    <script src="{{ asset('js/AppDesigner.js') }}"></script>
@endsection

@section('sidebar')
    @include('sidebars.default')
@endsection