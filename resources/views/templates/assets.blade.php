@extends('layouts.layout')

@section('title')
    {{__('Template Assets')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index')
    ]])
@endsection

@section('content')
<div id="template-asset-manager">
    <template-assets-view
        :assets="assets"
        :name="name"
        :responseId="responseId"
        :request="request"
        :redirectTo="redirectTo"
        :wizardTemplateUuid="wizardTemplateUuid"
        >
    </template-assets-view>
</div>
@endsection

@section('js')
     <script src="{{ mix('js/templates/assets.js') }}"></script>
@endsection
