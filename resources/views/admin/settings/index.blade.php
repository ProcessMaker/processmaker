@extends('layouts.layout')

@section('title')
    {{__('Settings')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Settings') => null,
    ]])
@endsection
@section('content')
    <div class="px-3" id="settings">
        <settings-groups ref="settings-groups"></settings-groups>
    </div>
    @isset($addons)
        @foreach ($addons as $addon)
            {!! $addon['content'] ?? '' !!}
        @endforeach
    @endisset
@endsection

@section('js')
    @vite('resources/js/admin/settings/index.js')
@endsection
