@extends('layouts.layout')

@section('title')
    {{__('Ldap Logs')}}
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
    <div class="px-3" id="ldap-logs">
    <ldap-logs></ldap-logs>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/settings/ldaplogs.js')}}"></script>
@endsection
