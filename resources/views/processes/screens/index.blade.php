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
            __('My Templates'),
            __('Public Templates'),
        ],
        'listConfig' => $listConfig,
        'catConfig' => $catConfig,
        'listScreenTemplates' => $listScreenTemplates,
    ])
        @slot('itemList')
            @component('processes.screens.list', ['config' => $listConfig])
            @endcomponent
        @endslot

        @slot('categoryList')
            @component('categories.list', ['config' => $catConfig])
            @endcomponent
        @endslot

        @slot('myTemplatesList')
            @component('processes.screens.myTemplates', ['config' => $listScreenTemplates->myScreenTemplates])
            @endcomponent
        @endslot

        @slot('publicTemplatesList')
            @component('processes.screens.publicTemplates', ['config' => $listScreenTemplates->publicScreenTemplates])
            @endcomponent
        @endslot
    @endcomponent
@endsection

@section('js')
@endsection

