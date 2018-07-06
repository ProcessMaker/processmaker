@extends('layouts.layout')
@section('content')
    <div id="designer-container"></div>
@endsection

@section('sidebar')
    @include('sidebars.default', ['sidebar'=> Menu::get('sidebar_designer')])
@endsection

@section('js')
    <script src="{{ mix('js/designer/main.js') }}"></script>
@endsection
