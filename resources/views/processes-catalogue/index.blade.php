@extends('layouts.layout')

@section('title')
    {{__('Processes Catalogue')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_processes_catalogue')])
@endsection

@section('content')
  <div class="px-3 page-content mb-0" id="processes-catalogue">
    <processes-catalogue></processes-catalogue>
  </div>
@endsection

@section('js')
    @vite('resources/js/processes-catalogue/index.js')
@endsection
