@extends('layouts.layout')

@section('title')
    @php
        $title = __('Processes');
        $status = request()->get('status');
        if( $status === 'archived'){
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
            __('Templates'),
            __('Categories'),
            __('Archived Processes'),
            __('Translations'),
        ],
        'listConfig' => $listConfig,
        'catConfig' => $catConfig,
        'listTemplates' => $listTemplates,
        'listTranslations' => $listTranslations,
    ])
        @slot('itemList')
            @component('processes.list', ['config' => $listConfig])
            @endcomponent
        @endslot
       
        @slot('templatesList')
            @component('templates.list', ['config' => $listTemplates])
            @endcomponent
        @endslot

        @slot('translationsList')
            @component('translations.list', ['config' => $listTranslations])
            @endcomponent
        @endslot
    
        @slot('categoryList')
            @component('categories.list', ['config' => $catConfig])
            @endcomponent
        @endslot

        @slot('archivedList')
            @component('processes.archivedList', ['config' => $listConfig])
            @endcomponent
        @endslot
    @endcomponent
@endsection

@section('js')
@endsection

