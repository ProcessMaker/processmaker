@extends('layouts.layout')

@section('title')
    {{ __('Plugins') }}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Plugins') => null,
    ]])
@endsection

@section('content')
    <div id="plugins-app" class="px-3">
    </div>
@endsection

@section('js')
    @vite('resources/js/admin/plugins/index.js')
@endsection
