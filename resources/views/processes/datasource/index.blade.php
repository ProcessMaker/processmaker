@extends('layouts.layout')

@section('title')
    {{__('Data Sources')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Data Sources') => null,
    ]])
@endsection

@section('content')
    @component('components.categorized_resource', [
            'tabs' => [
            __('Sources'),
            __('Categories'),
        ],
        'listConfig' => $listConfig,
        'catConfig' => $catConfig
    ])
        @slot('itemList')
            @include('processes.datasource.list', ['config' => $listConfig])
        @endslot

        @slot('categoryList')
            @include('categories.list', ['config' => $catConfig])
        @endslot

    @endcomponent
@endsection

@section('js')
@endsection
