@extends('layouts.layout')

@section('title')
    {{__('Queue Management')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Queue Management') => null,
    ]])
@endsection

@section('content')
    <iframe class="iframe-horizon mb-n1" src="/admin/horizon"></iframe>
@endsection

@section('css')
    <style>
        .main {
            padding: 0 !important;
        }
    
        .iframe-horizon {
            border: 0;
            height: 100%;
            width: 100%;
        }
    </style>
@endsection
