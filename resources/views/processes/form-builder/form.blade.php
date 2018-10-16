@extends('layouts.layout')

@section('title')
    {{__('Forms Builder')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div id="form-container">
        <form-builder :form="{{$form}}"></form-builder>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/form-builder/main.js')}}"></script>
@endsection
