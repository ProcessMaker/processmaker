@extends('layouts.layout',['content_margin' => '', 'overflow-auto' => ''])

@section('title')
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_cases')])
@endsection

@section('content')
<div id="cases-main"></div>
@endsection

@section('js')
<script>
    const currentUser = @json($currentUser);
  </script>
<script src="{{mix('js/composition/cases/casesMain/main.js')}}"></script>
@endsection

@section('css')
@endsection
