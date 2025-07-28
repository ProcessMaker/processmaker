@extends('layouts.layout')

@section('title')
    {{__('Tenant Jobs Dashboard')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Tenant Jobs') => null,
    ]])
@endsection

@section('content')
    <div id="tenant-queues-dashboard">
        <router-view></router-view>
    </div>
@endsection

@section('js')
    <script src="{{ mix('js/admin/tenant-queues/index.js') }}"></script>
@endsection
