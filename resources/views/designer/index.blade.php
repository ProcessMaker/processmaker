@extends('layouts.layout')

@section('title')
    @php
        $title = __('Designer');
    @endphp
    {{$title}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('designer.index'),
        $title => null,
    ]])
@endsection
@section('content')
    {{-- This section is to add new panels --}}
@endsection

@section('js')
@endsection

