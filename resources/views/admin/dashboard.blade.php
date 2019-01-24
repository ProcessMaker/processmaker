@extends('layouts.layout')

@section('title')
    {{__('Admin')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div class="container page-content">
    <h2>Admin Dashboard</h2>
</div>
@endsection