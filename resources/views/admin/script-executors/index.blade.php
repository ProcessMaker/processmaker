@extends('layouts.layout')

@section('title')
    {{ __('Script Executors') }}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Script Executors') => null,
    ]])
@endsection
@section('content')
    <div id="script-executors" class="px-3">
        <div class="card card-body">
            <script-executors></script-executors>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/script-executors/index.js')}}"></script>
@endsection

