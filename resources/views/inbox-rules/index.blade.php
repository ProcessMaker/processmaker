@extends('layouts.layoutnextvite')

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
    @vite('resources/js/tasks/loaderTasks.js')
    <script>
        window.Processmaker.defaultColumns = @json($defaultColumns);
    </script>
    @vite('resources/js/inbox-rules/index.js')
@endsection

@section('css')
    <style>
    </style>
@endsection
