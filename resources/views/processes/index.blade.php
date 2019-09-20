@extends('layouts.layout')

@section('title')
    @php
        $title = __('Processes');
        $status = request()->get('status');
        if( $status === 'inactive'){
            $title = __('Archived Processes');
        }
    @endphp
    {{$title}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        $title => null,
    ]])
@endsection
@section('content')
    @component('components.categorized_resource', [
            'tabs' => [
            __('Processes'),
            __('Categories'),
        ],
        'countCategories' => $countCategories,
        'showCategoriesTab' => $showCategoriesTab
    ])
        @slot('itemList')
            @include('processes.list')
        @endslot

        @slot('categoryList')
            @include('categories.list')
        @endslot

    @endcomponent
@endsection

@section('js')
@endsection

