@extends('layouts.layout')
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
    @component('components.categorized_resource', [
            'tabs' => [
            __('Scripts'),
            __('Categories'),
        ],
        'listConfig' => $listConfig,
        'catConfig' => $catConfig
    ])
        @slot('itemList')
            @component('processes.scripts.list', ['config' => $listConfig])
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

