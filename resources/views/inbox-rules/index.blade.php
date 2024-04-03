@extends('layouts.layout')

@section('title')
    {{__('Inbox Rules')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_task')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Inbox Rules') => route('inbox-rules.index'),
    ]])
@endsection
@section('content')
    <div id="inbox-rules">
        <router-view />
    </div>
@endsection

@section('js')
    <script>
        window.Processmaker.defaultColumns = @json($defaultColumns);
    </script>
    <script src="{{mix('js/inbox-rules/index.js')}}"></script>
    <script src="{{mix('js/inbox-rules/show.js')}}"></script>
@endsection

@section('css')
    <style>
    </style>
@endsection
