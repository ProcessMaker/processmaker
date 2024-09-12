@extends('layouts.layout')

<link href="{{ mix('css/tailwind.css') }}" rel="stylesheet">

@section('title')
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_request')])
@endsection

@section('breadcrumbs')
@endsection

@section('content')
<div id="cases-main"></div>
@endsection

@section('js')
<script src="{{mix('js/composition/cases/casesMain/main.js')}}"></script>
@endsection

@section('css')
@endsection
