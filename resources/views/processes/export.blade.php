@extends('layouts.layout')

@section('title')
{{__('Export Process')}}
@endsection

@section('meta')
    <meta name="export-process-name" content="{{ $process->name }}">
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        __('Export') => null,
    ]])
@endsection
@section('content')

<div id="export-manager">
    <router-view></router-view>
</div>

@endsection

@section('js')
    <script src="{{ mix('js/processes/export/index.js') }}"></script>
@endsection
