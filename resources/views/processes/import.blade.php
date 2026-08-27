@extends('layouts.layoutnextvite')

@section('title')
    {{__('Import Process')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        __('Import') => null,
    ]])
@endsection
@section('content')
  <div id="import-manager">
    <router-view></router-view>
  </div>
@endsection

@section('js')
  <script>
    window.temporal = window.temporal || {};
    window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
    window.packages = window.temporal.packages;
    window.temporal.importIsRunning = @json((bool) $importIsRunning);
    window.temporal.queueImports = @json((bool) config('app.queue_imports'));
  </script>
  @vite(['resources/js/processes/loaderProcesses.js'])
  @vite(['resources/js/processes/import/index.js'])
@endsection
