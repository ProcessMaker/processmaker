@extends('layouts.layout',['content_margin' => '', 'overflow-auto' => ''])

@section('title')
  {{ __('Cases') }}
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
@vite('resources/js/composition/cases/casesMain/main.js')
@endsection

@section('css')
@endsection
