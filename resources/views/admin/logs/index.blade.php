@extends('layouts.layout')

@section('title')
    {{__('Logs')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Logs') => null,
    ]])
@endsection

@section('content')
    <div class="page-content px-3 mb-0" id="admin-logs-main"></div>
@endsection

@section('js')
    <script>
        const permission = @json(\Auth::user()->hasPermissionsFor('settings'));
    </script>
    <script src="{{mix('js/admin/logs/index.js')}}"></script>
@endsection

