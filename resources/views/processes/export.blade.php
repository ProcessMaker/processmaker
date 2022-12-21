@extends('layouts.layout')

@section('title')
{{__('Export Process')}}
@endsection

@section('meta')
    <meta name="export-process-name" content="{{ $process->name }}">
    <meta name="export-process-description" content="{{ $process->description }}">
    <meta name="export-process-category" content="{{ $process->category->name }}">
    <meta name="export-process-manager" content="{{ $process->manager->fullname }}">
    <meta name="export-process-created-at" content="{{ $process->created_at }}">
    <meta name="export-process-updated-at" content="{{ $process->updated_at }}">
    <meta name="export-process-updated-by" content="{{ $process->updatedBy }}">

@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        __('Export') => null,
    ]])
@endsection
@section('content')

<div id="export-manager">
    <router-view></router-view>
</div>

@endsection

@section('js')
    <script src="{{ mix('js/processes/export/index.js') }}"></script>
@endsection
