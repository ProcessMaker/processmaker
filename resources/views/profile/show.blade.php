@extends('layouts.layout')

@section('title')
  {{__('Profile')}}
@endsection

@section('content')

@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_designer')])
@endsection

@section('js')
@endsection