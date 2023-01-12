@extends('layouts.layout')

@section('title')
{{__('Export Process')}}
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

<div id="nav-test">
    <pm-container>
        <pm-content header="Item 1">Item 1</pm-content>
        <pm-content header="Item 2">Item 2</pm-content>
        <pm-content header="Item 3">Item 3</pm-content>
    </pm-container>
</div>

@endsection

@section('js')
    <script src="{{ mix('js/processes/export/nav.js') }}"></script>
@endsection
