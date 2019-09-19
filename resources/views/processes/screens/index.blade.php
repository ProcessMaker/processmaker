@extends('layouts.layout')

@section('title')
    {{__('Screens')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Screens') => null,
    ]])
@endsection

@section('content')
    @component('components.categorized_resource', [
            'tabs' => [
            __('Screens'),
            __('Categories'),
        ],
        'countCategories' => $countCategories,
        'showCategoriesTab' => $showCategoriesTab
    ])
        @slot('itemList')
            @include('processes.screens.list')
        @endslot

        @slot('categoryList')
            @include('categories.list')
        @endslot

    @endcomponent
@endsection

@section('js')
@endsection

