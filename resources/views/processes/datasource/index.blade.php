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
        'countCategories' => $countCategories,
        'showCategoriesTab' => $showCategoriesTab
    ])
        @slot('itemList')
            @include('processes.datasource.list')
        @endslot

        @slot('categoryList')
            @include('categories.list')
        @endslot

    @endcomponent
@endsection

@section('js')
@endsection
