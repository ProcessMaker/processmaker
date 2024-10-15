@extends('layouts.layout')

@section('title')
    {{__('DevLink')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('DevLink') => null,
    ]])
@endsection
@section('content')
    <div class="px-3" id="devlink">
        <dev-link></dev-link>
    </div>
@endsection


@section('js')
    <script src="{{mix('js/admin/devlink/index.js')}}"></script>
@endsection
