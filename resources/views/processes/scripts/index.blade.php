@extends('layouts.layoutnextvite')
@section('title')
    {{__('Scripts')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Scripts') => null,
    ]])
@endsection

@section('content')
    <script>
        window.temporal = window.temporal || {};
        window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
        window.packages = window.temporal.packages;
    </script>
    @vite(['resources/js/processes/scripts/loaderScripts.js'])
    @component('components.categorized_resource', [
            'tabs' => [
            __('Scripts'),
            __('Categories'),
        ],
        'listConfig' => $listConfig,
        'catConfig' => $catConfig,
        'runAsUserDefault' => $runAsUserDefault,
    ])
        @slot('itemList')
            @component('processes.scripts.list', ['config' => $listConfig, 'runAsUserDefault' => $runAsUserDefault])
            @endcomponent
        @endslot

        @slot('categoryList')
            @component('categories.list', ['config' => $catConfig])
            @endcomponent
        @endslot

    @endcomponent
@endsection

@section('js')
@endsection
