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
        'listConfig' => $listConfig,
        'catConfig' => $catConfig
    ])
        @slot('itemList')
            @component('processes.screens.list', ['config' => $listConfig])
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

