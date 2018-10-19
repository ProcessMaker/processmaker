@extends('layouts.layout')

@section('title')
  {{__('Edit Requests')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
@endsection

@section('js')
@endsection