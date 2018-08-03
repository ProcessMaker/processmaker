@extends('layouts.layout')
@section('content')
    <div id="form-container">
        <form-builder :process="{{$process}}" :form="{{$form}}"></form-builder>
    </div>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_designer')])
@endsection

@section('js')
    <script src="{{ mix('js/formBuilder/main.js') }}"></script>
@endsection
