@extends('layouts.layout')

@section('title')
    {{__('Import Template')}}
@endsection

@section('meta')
    <meta name="import-template-asset-type" content="{{ $type }}">
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        __('Templates') => route('processes.index'),
        __('Import') => null,
    ]])
@endsection
@section('content')
  <div id="import-manager">
    <router-view></router-view>
  </div>
@endsection

@section('js')
  <script src="{{ mix('js/templates/import/index.js') }}"></script>
@endsection
