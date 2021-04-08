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
        <settings-groups></settings-groups>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/settings/index.js')}}"></script>
@endsection
