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
    <div class="px-3" id="emailLogs">
    {{ __('Email Logs') }}
    </div>
@endsection


@section('js')

@endsection
