@extends('layouts.layoutnextvite')

@section('title')
    {{__('Logs')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Logs') => null,
    ]])
@endsection

@section('content')
    <div class="page-content px-3 mb-0" id="admin-logs-main"></div>
@endsection

@section('js')
    <script>
        window.temporal = window.temporal || {};
        window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
        window.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
        const permission = @json(\Auth::user()->hasPermissionsFor('settings'));
    </script>
    @vite(['resources/js/admin/loaderAdmin.js'])
    @vite(['resources/js/admin/logs/index.js'])
@endsection

