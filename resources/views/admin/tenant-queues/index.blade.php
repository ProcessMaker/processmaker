@extends('layouts.layoutnextvite')

@section('title')
    {{__('Jobs Dashboard')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Jobs') => null,
    ]])
@endsection

@section('content')
    @vite(['resources/js/admin/loaderAdmin.js'])
    <div id="tenant-queues-dashboard">
        <router-view></router-view>
    </div>
@endsection

@section('js')
    <script>
        window.temporal = window.temporal || {};
        window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
        window.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
    </script>
    @vite(['resources/js/admin/tenant-queues/index.js'])
@endsection
