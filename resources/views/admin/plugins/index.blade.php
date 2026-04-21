@extends('layouts.layout')

@section('title')
    {{__('Plugins')}}
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
    <div id="plugins" class="px-3">
        <plugins data-permission='@json(\Auth::user()->hasPermissionsFor('plugins'))'></plugins>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/plugins/index.js')}}"></script>
@endsection