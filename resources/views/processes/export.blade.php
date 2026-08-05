@extends('layouts.layoutnextvite')

@section('title')
{{__('Export Process')}}
@endsection

@section('meta')
    <meta name="export-process-name" content="{{ $process->name }}">
    <meta name="export-process-id" content="{{ $process->id }}">
    <meta name="export-project-id" content="{{ $projectId }}">
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
    <script>
        window.temporal = window.temporal || {};
        window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
        window.packages = window.temporal.packages;
    </script>
    @vite(['resources/js/processes/loaderProcesses.js'])
    @vite(['resources/js/processes/export/index.js'])
@endsection
