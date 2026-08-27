@extends('layouts.layoutnextvite')

@section('title')
    {{__('DevLink')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('DevLink') => null,
    ]])
@endsection
@section('content')
    @vite(['resources/js/admin/loaderAdmin.js'])
    <div class="px-3" id="devlink">
        <dev-link></dev-link>
    </div>
@endsection


@section('js')
    <script>
        window.temporal = window.temporal || {};
        window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
        window.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
    </script>
    @vite(['resources/js/admin/devlink/index.js'])
@endsection
