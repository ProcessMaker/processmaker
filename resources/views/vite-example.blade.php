@extends('layouts.layout')

@section('title')
    {{ __('Vite Example') }}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Vite Example') => null,
    ]])
@endsection

@section('content')
    <div class="px-3">
        <p>{{ __('This page loads its script via Vite.') }}</p>
        <div id="vite-example-app"></div>
    </div>
@endsection

@section('js')
    @vite('resources/js/vite-entries/example-page.js')
@endsection
