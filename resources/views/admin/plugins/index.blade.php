@extends('layouts.layoutnextvite')

@section('title')
    {{__('Plugins')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Plugins') => null,
    ]])
@endsection
@section('content')
    <div id="plugins" class="px-3">
        <plugins data-permission='@json(\Auth::user()->hasPermissionsFor('plugins'))'></plugins>
    </div>
@endsection

@section('js')
    @vite('resources/js/loader/loaderMinimal.js')
    <script>
        window.temporal = window.temporal || {};
        window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
        window.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
    </script>
    @vite(['resources/js/admin/plugins/index.js'])
@endsection